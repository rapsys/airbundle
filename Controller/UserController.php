<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys UserBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use Rapsys\PackBundle\Util\SluggerUtil;

use Rapsys\UserBundle\Controller\DefaultController;

class UserController extends DefaultController {
	/**
	 * {@inheritdoc}
	 */
	public function edit(Request $request, Registry $doctrine, UserPasswordEncoderInterface $encoder, EntityManagerInterface $manager, SluggerUtil $slugger, $mail, $hash): Response {
		//With invalid hash
		if ($hash != $slugger->hash($mail)) {
			//Throw bad request
			throw new BadRequestHttpException($this->translator->trans('Invalid %field% field: %value%', ['%field%' => 'hash', '%value%' => $hash]));
		}

		//Get mail
		$mail = $slugger->unshort($smail = $mail);

		//With existing subscriber
		if (empty($user = $doctrine->getRepository($this->config['class']['user'])->findOneByMail($mail))) {
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

		//With admin
		if ($this->isGranted('ROLE_ADMIN')) {
			//With pseudonym and without slug
			if (!empty($pseudonym = $user->getPseudonym()) && empty($user->getSlug())) {
				//Preset slug
				$user->setSlug($slugger->slug($pseudonym));
			}
		}

		//Create the RegisterType form and give the proper parameters
		$edit = $this->createForm($this->config['edit']['view']['edit'], $user, [
			//Set action to register route name and context
			'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $slugger->hash($smail)]+$this->config['route']['edit']['context']),
			//Set civility class
			'civility_class' => $this->config['class']['civility'],
			//Set civility default
			'civility_default' => $doctrine->getRepository($this->config['class']['civility'])->findOneByTitle($this->config['default']['civility']),
			//Disable mail
			'mail' => $this->isGranted('ROLE_ADMIN'),
			//Disable pseudonym
			'pseudonym' => $this->isGranted('ROLE_GUEST'),
			//Disable slug
			'slug' => $this->isGranted('ROLE_ADMIN'),
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
				'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $slugger->hash($smail)]+$this->config['route']['edit']['context']),
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
					$data->setPassword($encoder->encodePassword($data, $data->getPassword()));

					//Queue snippet save
					$manager->persist($data);

					//Flush to get the ids
					$manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Account %mail% password updated', ['%mail%' => $mail = $data->getMail()]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail = $slugger->short($mail), 'hash' => $slugger->hash($smail)]+$this->config['route']['edit']['context']);
				}
			}

			//Add reset view
			$this->config['edit']['view']['context']['reset'] = $reset->createView();
		//Without admin role
		//XXX: prefer a reset on login to force user unspam action
		} else {
			//Add notice
			$this->addFlash('notice', $this->translator->trans('To change your password login with your mail and any password then follow the procedure'));
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
				$manager->persist($data);

				//Try saving in database
				try {
					//Flush to get the ids
					$manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Account %mail% updated', ['%mail%' => $mail = $data->getMail()]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail = $slugger->short($mail), 'hash' => $slugger->hash($smail)]+$this->config['route']['edit']['context']);
				//Catch double slug or mail
				} catch (UniqueConstraintViolationException $e) {
					//Add error message mail already exists
					$this->addFlash('error', $this->translator->trans('Account %mail% or with slug %slug% already exists', ['%mail%' => $data->getMail(), '%slug%' => $slug]));
				}
			}
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
