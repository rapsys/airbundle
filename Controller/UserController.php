<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rapsys\UserBundle\Controller\UserController as BaseUserController;

class UserController extends BaseUserController {
	/**
	 * {@inheritdoc}
	 */
	public function edit(Request $request, string $hash, string $mail): Response {
		//With invalid hash
		if ($hash != $this->slugger->hash($mail)) {
			//Throw bad request
			throw new BadRequestHttpException($this->translator->trans('Invalid %field% field: %value%', ['%field%' => 'hash', '%value%' => $hash]));
		}

		//Get mail
		$mail = $this->slugger->unshort($smail = $mail);

		//With existing subscriber
		if (empty($user = $this->doctrine->getRepository($this->config['class']['user'])->findOneByMail($mail))) {
			//Throw not found
			//XXX: prevent slugger reverse engineering by not displaying decoded mail
			throw $this->createNotFoundException($this->translator->trans('Unable to find account %mail%', ['%mail%' => $smail]));
		}

		//Prevent access when not admin, user is not guest and not currently logged user
		if (!$this->isGranted('ROLE_ADMIN') && $user != $this->getUser() || !$this->isGranted('IS_AUTHENTICATED_FULLY')) {
			//Throw access denied
			//XXX: prevent slugger reverse engineering by not displaying decoded mail
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access user: %mail%', ['%mail%' => $smail]));
		}

		//Create the RegisterType form and give the proper parameters
		$edit = $this->createForm($this->config['edit']['view']['edit'], $user, [
			//Set action to register route name and context
			'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $this->slugger->hash($smail)]+$this->config['route']['edit']['context']),
			//Set civility class
			'civility_class' => $this->config['class']['civility'],
			//Set civility default
			'civility_default' => $this->doctrine->getRepository($this->config['class']['civility'])->findOneByTitle($this->config['default']['civility']),
			//Set country class
			'country_class' => $this->config['class']['country'],
			//Set country default
			'country_default' => $this->doctrine->getRepository($this->config['class']['country'])->findOneByTitle($this->config['default']['country']),
			//Set country favorites
			'country_favorites' => $this->doctrine->getRepository($this->config['class']['country'])->findByTitle($this->config['default']['country_favorites']),
			//Disable mail
			'mail' => $this->isGranted('ROLE_ADMIN'),
			//Disable pseudonym
			'pseudonym' => $this->isGranted('ROLE_GUEST'),
			//Disable password
			'password' => false,
			//Set method
			'method' => 'POST'
		]+$this->config['edit']['field']);

		//With admin role
		if ($this->isGranted('ROLE_ADMIN')) {
			//Create the LoginType form and give the proper parameters
			$reset = $this->createForm($this->config['edit']['view']['reset'], $user, [
				//Set action to register route name and context
				'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $this->slugger->hash($smail)]+$this->config['route']['edit']['context']),
				//Disable mail
				'mail' => false,
				//Set method
				'method' => 'POST'
			]);

			//With post method
			if ($request->isMethod('POST')) {
				//Refill the fields in case the form is not valid.
				$reset->handleRequest($request);

				//With reset submitted and valid
				if ($reset->isSubmitted() && $reset->isValid()) {
					//Set data
					$data = $reset->getData();

					//Set password
					$data->setPassword($this->hasher->hashPassword($data, $data->getPassword()));

					//Queue snippet save
					$this->manager->persist($data);

					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Account %mail% password updated', ['%mail%' => $mail = $data->getMail()]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail = $this->slugger->short($mail), 'hash' => $this->slugger->hash($smail)]+$this->config['route']['edit']['context']);
				}
			}

			//Add reset view
			$this->config['edit']['view']['context']['reset'] = $reset->createView();
		}

		//With post method
		if ($request->isMethod('POST')) {
			//Refill the fields in case the form is not valid.
			$edit->handleRequest($request);

			//With edit submitted and valid
			if ($edit->isSubmitted() && $edit->isValid()) {
				//Set data
				$data = $edit->getData();

				//Queue snippet save
				$this->manager->persist($data);

				//Try saving in database
				try {
					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Account %mail% updated', ['%mail%' => $mail = $data->getMail()]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail = $this->slugger->short($mail), 'hash' => $this->slugger->hash($smail)]+$this->config['route']['edit']['context']);
				//Catch double slug or mail
				} catch (UniqueConstraintViolationException $e) {
					//Add error message mail already exists
					$this->addFlash('error', $this->translator->trans('Account %mail% already exists', ['%mail%' => $data->getMail()]));
				}
			}
		//Without admin role
		//XXX: prefer a reset on login to force user unspam action
		} elseif (!$this->isGranted('ROLE_ADMIN')) {
			//Add notice
			$this->addFlash('notice', $this->translator->trans('To change your password login with your mail and any password then follow the procedure'));
		}

		//Render view
		return $this->render(
			//Template
			$this->config['edit']['view']['name'],
			//Context
			['edit' => $edit->createView(), 'sent' => $request->query->get('sent', 0)]+$this->config['edit']['view']['context']
		);
	}
}
