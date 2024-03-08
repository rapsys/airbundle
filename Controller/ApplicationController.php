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

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMInvalidArgumentException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;

/**
 * {@inheritdoc}
 */
class ApplicationController extends AbstractController {
	/**
	 * Add application
	 *
	 * @desc Persist application and all required dependencies in database
	 *
	 * @param Request $request The request instance
	 * @param Registry $manager The doctrine registry
	 * @param EntityManagerInterface $manager The doctrine entity manager
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function add(Request $request) {
		//Without guest role
		if (!$this->checker->isGranted('ROLE_GUEST')) {
			//Throw 403
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));
		}

		//Get favorites dances
		$danceFavorites = $this->doctrine->getRepository(Dance::class)->findByUserId($this->security->getUser()->getId());

		//Set dance default
		$danceDefault = !empty($danceFavorites)?current($danceFavorites):null;

		//Get favorites locations
		$locationFavorites = $this->doctrine->getRepository(Location::class)->findByUserId($this->security->getUser()->getId());

		//Set location default
		$locationDefault = !empty($locationFavorites)?current($locationFavorites):null;

		//With admin
		if ($this->checker->isGranted('ROLE_ADMIN')) {
			//Get dances
			$dances = $this->doctrine->getRepository(Dance::class)->findAll();

			//Get locations
			$locations = $this->doctrine->getRepository(Location::class)->findAll();
		//Without admin
		} else {
			//Restrict to favorite dances
			$dances = $danceFavorites;

			//Reset favorites
			$danceFavorites = [];

			//Restrict to favorite locations
			$locations = $locationFavorites;

			//Reset favorites
			$locationFavorites = [];
		}

		//Create ApplicationType form
		$form = $this->factory->create('Rapsys\AirBundle\Form\ApplicationType', null, [
			//Set the action
			'action' => $this->generateUrl('rapsysair_application_add'),
			//Set the form attribute
			#'attr' => [ 'class' => 'col' ],
			//Set dance choices
			'dance_choices' => $dances,
			//Set dance default
			'dance_default' => $danceDefault,
			//Set dance favorites
			'dance_favorites' => $danceFavorites,
			//Set location choices
			'location_choices' => $locations,
			//Set location default
			'location_default' => $locationDefault,
			//Set location favorites
			'location_favorites' => $locationFavorites,
			//With user
			'user' => $this->checker->isGranted('ROLE_ADMIN'),
			//Set user choices
			'user_choices' => $this->doctrine->getRepository(User::class)->findChoicesAsArray(),
			//Set default user to current
			'user_default' => $this->security->getUser()->getId(),
			//Set default slot to evening
			//XXX: default to Evening (3)
			'slot_default' => $this->doctrine->getRepository(Slot::class)->findOneByTitle('Evening')
		]);

		//Set title
		$this->context['title']['page'] = $this->translator->trans('Application add');

		//Set section
		$this->context['title']['section'] = $this->translator->trans('Application');

		//Set description
		$this->context['description'] = $this->translator->trans('Add an application and session');

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			//Render the view
			return $this->render('@RapsysAir/application/add.html.twig', ['form' => $form->createView()]+$this->context);
		}

		//Get data
		$data = $form->getData();

		//Protect session fetching
		try {
			//Fetch session
			$session = $this->doctrine->getRepository(Session::class)->findOneByLocationSlotDate($data['location'], $data['slot'], $data['date']);
		//Catch no session case
		} catch (NoResultException $e) {
			//Create the session
			$session = new Session($data['date'], $data['location'], $data['slot']);

			//Get location
			$location = $data['location']->getTitle();

			//Get slot
			$slot = $data['slot']->getTitle();

			//Get premium
			//XXX: premium is stored only for Afternoon and Evening
			$premium = $session->isPremium();

			//Set default length at 6h
			//XXX: date part will be truncated on save
			$session->setLength(new \DateTime('06:00:00'));

			//Check if admin
			if ($this->checker->isGranted('ROLE_ADMIN')) {
				//Check if morning
				if ($slot == 'Morning') {
					//Set begin at 9h
					$session->setBegin(new \DateTime('09:00:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:00:00'));
				//Check if afternoon
				} elseif ($slot == 'Afternoon') {
					//Set begin at 18h
					$session->setBegin(new \DateTime('15:30:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:30:00'));
				//Check if evening
				} elseif ($slot == 'Evening') {
					//Set begin at 19h00
					$session->setBegin(new \DateTime('19:30:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:30:00'));

					//Check if next day is premium
					if ($premium) {
						//Set length at 7h
						$session->setLength(new \DateTime('06:30:00'));
					}
				//Check if after
				} else {
					//Set begin at 1h
					$session->setBegin(new \DateTime('01:00:00'));

					//Set length at 4h
					$session->setLength(new \DateTime('04:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set begin at 2h
						$session->setBegin(new \DateTime('02:00:00'));

						//Set length at 3h
						$session->setLength(new \DateTime('03:00:00'));
					}
				}
			//Tino-Rossi garden => 14h -> 19h | 19h -> 01/02h
			} elseif (in_array($location, ['Tino-Rossi garden']) && in_array($slot, ['Afternoon', 'Evening', 'After'])) {
				//Check if afternoon
				if ($slot == 'Afternoon') {
					//Set begin at 14h
					$session->setBegin(new \DateTime('14:00:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:00:00'));
				//Check if evening
				} elseif ($slot == 'Evening') {
					//Set begin at 19h
					$session->setBegin(new \DateTime('19:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set length at 7h
						$session->setLength(new \DateTime('07:00:00'));
					}
				//Check if after
				} else {
					//Set begin at 1h
					$session->setBegin(new \DateTime('01:00:00'));

					//Set length at 4h
					$session->setLength(new \DateTime('04:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set begin at 2h
						$session->setBegin(new \DateTime('02:00:00'));

						//Set length at 3h
						$session->setLength(new \DateTime('03:00:00'));
					}
				}
			//Garnier opera => 21h -> 01/02h
			} elseif ($location == 'Garnier opera' && in_array($slot, ['Evening', 'After'])) {
				//Check if evening
				if ($slot == 'Evening') {
					//Set begin at 21h
					$session->setBegin(new \DateTime('21:00:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set length at 6h
						$session->setLength(new \DateTime('06:00:00'));
					}
				//Check if after
				} else {
					//Set begin at 1h
					$session->setBegin(new \DateTime('01:00:00'));

					//Set length at 4h
					$session->setLength(new \DateTime('04:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set begin at 2h
						$session->setBegin(new \DateTime('02:00:00'));

						//Set length at 3h
						$session->setLength(new \DateTime('03:00:00'));
					}
				}
			//Trocadero esplanade|Tokyo palace|Swan island|Saint-Honore market|Orsay museum => 19h -> 01/02h
			} elseif (in_array($location, ['Trocadero esplanade', 'Tokyo palace', 'Swan island', 'Saint-Honore market', 'Orsay museum']) && in_array($slot, ['Evening', 'After'])) {
				//Check if evening
				if ($slot == 'Evening') {
					//Set begin at 19h
					$session->setBegin(new \DateTime('19:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set length at 7h
						$session->setLength(new \DateTime('07:00:00'));
					}
				//Check if after
				} else {
					//Set begin at 1h
					$session->setBegin(new \DateTime('01:00:00'));

					//Set length at 4h
					$session->setLength(new \DateTime('04:00:00'));

					//Check if next day is premium
					if ($premium) {
						//Set begin at 2h
						$session->setBegin(new \DateTime('02:00:00'));

						//Set length at 3h
						$session->setLength(new \DateTime('03:00:00'));
					}
				}
			//Drawings' garden (Villette) => 14h -> 19h
			} elseif ($location == 'Drawings\' garden' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 5h
				$session->setLength(new \DateTime('05:00:00'));
			//Colette place => 14h -> 21h
			//TODO: add check here that it's a millegaux account ?
			} elseif ($location == 'Colette place' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 7h
				$session->setLength(new \DateTime('07:00:00'));
			//Orleans gallery => 14h -> 18h
			} elseif ($location == 'Orleans gallery' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 4h
				$session->setLength(new \DateTime('04:00:00'));
			//Monde garden => 14h -> 19h
			//TODO: add check here that it's a raphael account ?
			} elseif ($location == 'Monde garden' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 4h
				$session->setLength(new \DateTime('05:00:00'));
			//Combination not supported
			//TODO: add Madeleine place|Bastille place|Vendome place ?
			} else {
				//Add error in flash message
				$this->addFlash('error', $this->translator->trans('Session on %date% %location% %slot% not yet supported', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower(strval($data['slot']))), '%date%' => $data['date']->format('Y-m-d')]));

				//Render the view
				return $this->render('@RapsysAir/application/add.html.twig', ['form' => $form->createView()]+$this->context);
			}

			//Check if admin
			if (!$this->checker->isGranted('ROLE_ADMIN') && $session->getStart() < new \DateTime('00:00:00')) {
				//Add error in flash message
				$this->addFlash('error', $this->translator->trans('Session in the past on %date% %location% %slot% not yet supported', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower(strval($data['slot']))), '%date%' => $data['date']->format('Y-m-d')]));

				//Render the view
				return $this->render('@RapsysAir/application/add.html.twig', ['form' => $form->createView()]+$this->context);
			}

			//Queue session save
			$this->manager->persist($session);

			//Flush to get the ids
			#$this->manager->flush();

			$this->addFlash('notice', $this->translator->trans('Session on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower(strval($data['slot']))), '%date%' => $data['date']->format('Y-m-d')]));
		}

		//Set user
		$user = $this->security->getUser();

		//Replace with requested user for admin
		if ($this->checker->isGranted('ROLE_ADMIN') && !empty($data['user'])) {
			$user = $this->doctrine->getRepository(User::class)->findOneById($data['user']);
		}

		//Protect application fetching
		try {
			//Retrieve application
			$application = $this->doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Add warning in flash message
			$this->addFlash('warning', $this->translator->trans('Application on %date% %location% %slot% already exists', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower(strval($data['slot']))), '%date%' => $data['date']->format('Y-m-d')]));
		//Catch no application and session without identifier (not persisted&flushed) cases
		} catch (NoResultException|ORMInvalidArgumentException $e) {
			//Create the application
			$application = new Application();
			$application->setDance($data['dance']);
			$application->setSession($session);
			$application->setUser($user);

			//Refresh session updated field
			$session->setUpdated(new \DateTime('now'));

			//Queue session save
			$this->manager->persist($session);

			//Queue application save
			$this->manager->persist($application);

			//Flush to get the ids
			$this->manager->flush();

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower(strval($data['slot']))), '%date%' => $data['date']->format('Y-m-d')]));
		}

		//Extract and process referer
		if ($referer = $request->headers->get('referer')) {
			//Create referer request instance
			$req = Request::create($referer);

			//Get referer path
			$path = $req->getPathInfo();

			//Get referer query string
			$query = $req->getQueryString();

			//Remove script name
			$path = str_replace($request->getScriptName(), '', $path);

			//Try with referer path
			try {
				//Save old context
				$oldContext = $this->router->getContext();

				//Force clean context
				//XXX: prevent MethodNotAllowedException because current context method is POST in onevendor/symfony/routing/Matcher/Dumper/CompiledUrlMatcherTrait.php+42
				$this->router->setContext(new RequestContext());

				//Retrieve route matching path
				$route = $this->router->match($path);

				//Reset context
				$this->router->setContext($oldContext);

				//Clear old context
				unset($oldContext);

				//Extract name
				$name = $route['_route'];

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller']);

				//Check if session view route
				if ($name == 'rapsysair_session_view' && !empty($route['id'])) {
					//Replace id
					$route['id'] = $session->getId();
				//Other routes
				} else {
					//Set session
					$route['session'] = $session->getId();
				}

				//Generate url
				return $this->redirectToRoute($name, $route);
			//No route matched
			} catch (MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsysair', ['session' => $session->getId()]);
	}
}
