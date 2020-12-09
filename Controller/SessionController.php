<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class SessionController extends DefaultController {
	/**
	 * Edit session
	 *
	 * @desc Persist session and all required dependencies in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function edit(Request $request, $id) {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_GUEST', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));

		//Reject non post requests
		if (!$request->isMethod('POST')) {
			throw new \RuntimeException('Request method MUST be POST');
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Fetch session
		$session = $doctrine->getRepository(Session::class)->fetchOneById($id);

		//Check if
		if (
			//we are admin
			!$this->isGranted('ROLE_ADMIN') &&
			//or attributed user
			$this->getUser()->getId() != $session['au_id'] &&
			//or application without attributed user
			$session['au_id'] !== null && !in_array($this->getUser()->getId(), explode("\n", $session['sau_id']))
		) {
			//Prevent non admin and non attributed user access
			throw $this->createAccessDeniedException();
		}

		//Set now
		$now = new \DateTime('now');

		//Create SessionEditType form
		$form = $this->createForm('Rapsys\AirBundle\Form\SessionEditType', null, [
			//Set the action
			'action' => $this->generateUrl('rapsys_air_session_edit', [ 'id' => $id ]),
			//Set the form attribute
			'attr' => [],
			//Set admin
			'admin' => $this->isGranted('ROLE_ADMIN'),
			//Set default user to current
			'user' => $this->getUser()->getId(),
			//Set begin
			'begin' => $session['begin'],
			//Set length
			'length' => $session['length'],
			//Set raincancel
			'raincancel' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['rainfall'] >= 2,
			//Set cancel
			'cancel' => $this->isGranted('ROLE_ADMIN') || in_array($this->getUser()->getId(), explode("\n", $session['sau_id'])),
			//Set modify
			'modify' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_REGULAR'),
			//Set move
			'move' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_SENIOR'),
			//Set session
			'session' => $session['id']
		]);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid data
		#if (true) { $form->isValid();
		//TODO: mettre une contrainte sur un des boutons submit, je sais pas encore comment
		if (!$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Session %id%', ['%id%' => $id]);

			//Set title
			$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

			//Add session in context
			$context['session'] = [
				'id' => $id,
				'title' => $this->translator->trans('Session %id%', ['%id%' => $id]),
				'location' => [
					'id' => $session['l_id'],
					'at' => $this->translator->trans('at '.$session['l_title'])
				]
			];
			//Render the view
			return $this->render('@RapsysAir/session/edit.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$context+$this->context);
		}

		//Get manager
		$manager = $doctrine->getManager();

		//Get data
		$data = $form->getData();

		//Fetch session
		$session = $doctrine->getRepository(Session::class)->findOneById($id);

		//Set user
		$user = $this->getUser();

		//Replace with requested user for admin
		if ($this->isGranted('ROLE_ADMIN') && !empty($data['user'])) {
			$user = $doctrine->getRepository(User::class)->findOneById($data['user']);
		}

		//Set datetime
		$datetime = new \DateTime('now');

		//With raincancel
		if ($form->has('raincancel') && $form->get('raincancel')->isClicked()) {
			//Check rainfall
			if ($this->isGranted('ROLE_ADMIN') || $session->getRainfall() >= 2) {
				//Check that application is attributed
				if (!empty($application = $session->getApplication())) {
					//Get application
					$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $application->getUser());

					//Set canceled time at start minus one day
					$canceled = (clone $session->getStart())->sub(new \DateInterval('P1D'));

					//Cancel application
					$application->setCanceled($canceled);

					//Update time
					$application->setUpdated($datetime);

					//Queue application save
					$manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
					//Update time
					$session->setUpdated($datetime);

					//Queue session save
					$manager->persist($session);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//Not attributed
				} else {
					//Add notice in flash message
					$this->addFlash('warning', $this->translator->trans('Session %id% not updated', ['%id%' => $id]));
				}
			//Not enough rainfall
			} else {
				//Add notice in flash message
				$this->addFlash('warning', $this->translator->trans('Session %id% not updated', ['%id%' => $id]));
			}
		//With modify
		} elseif ($form->has('modify') && $form->get('modify')->isClicked()) {
			//Set begin
			$session->setBegin($data['begin']);

			//Set length
			$session->setLength($data['length']);

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		//With move
		} elseif ($form->has('move') && $form->get('move')->isClicked()) {
			//Set location
			$session->setLocation($doctrine->getRepository(Location::class)->findOneById($data['location']));

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		//With cancel
		} elseif ($form->has('cancel') && $form->get('cancel')->isClicked()) {
			//Get application
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Not already canceled
			if ($application->getCanceled() === null) {
				//Cancel application
				$application->setCanceled($datetime);

				//Check if application is session application
				if ($session->getApplication() == $application) {
					//Unattribute session
					$session->setApplication(null);

					//Update time
					$session->setUpdated($datetime);

					//Queue session save
					$manager->persist($session);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				}
			//Already canceled
			} else {
				//Uncancel application
				$application->setCanceled(null);
			}

			//Update time
			$application->setUpdated($datetime);

			//Queue application save
			$manager->persist($application);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
		//With attribute
		} elseif ($form->has('attribute') && $form->get('attribute')->isClicked()) {
			//Get application
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Already canceled
			if ($application->getCanceled() !== null) {
				//Uncancel application
				$application->setCanceled(null);
			}

			//Set score
			//XXX: magic cheat score 42
			$application->setScore(42);

			//Update time
			$application->setUpdated($datetime);

			//Queue application save
			$manager->persist($application);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));

			//Unattribute session
			$session->setApplication($application);

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		//With autoattribute
		} elseif ($form->has('autoattribute') && $form->get('autoattribute')->isClicked()) {
			//Get best application
			//XXX: best application may not issue result while grace time or bad behaviour
			if (!empty($application = $doctrine->getRepository(Session::class)->findBestApplicationById($id))) {
				//Attribute session
				$session->setApplication($application);

				//Update time
				$session->setUpdated($datetime);

				//Queue session save
				$manager->persist($session);

				//Add notice in flash message
				$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
			//No application
			} else {
				//Add notice in flash message
				$this->addFlash('warning', $this->translator->trans('Session %id% not updated', ['%id%' => $id]));
			}
		//With lock
		} elseif ($form->has('lock') && $form->get('lock')->isClicked()) {
			//Already locked
			if ($session->getLocked() !== null) {
				//Set uncanceled
				$canceled = null;

				//Unlock session
				$session->setLocked(null);
			//Not locked
			} else {
				//Set canceled time at start minus one day
				$canceled = (clone $session->getStart())->sub(new \DateInterval('P1D'));

				//Unattribute session
				$session->setApplication(null);

				//Lock session
				$session->setLocked($datetime);
			}

			//Get applications
			$applications = $doctrine->getRepository(Application::class)->findBySession($session);

			//Not empty
			if (!empty($applications)) {
				//Iterate on each applications
				foreach($applications as $application) {
					//Cancel application
					$application->setCanceled($canceled);

					//Update time
					$application->setUpdated($datetime);

					//Queue application save
					$manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
				}
			}

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		}

		//Flush to get the ids
		$manager->flush();

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

				//Generate url
				return $this->redirectToRoute($name, ['session' => $id]+$route);
			//No route matched
			} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air_session_view', ['id' => $id]);

		//Protect session fetching
		try {
			//Fetch session
			$session = $doctrine->getRepository(Session::class)->findOneById($id);

			//Fetch session
			$session = $doctrine->getRepository(Session::class)->findOneByLocationSlotDate($data['location'], $data['slot'], $data['date']);
		//Catch no session case
		} catch (\Doctrine\ORM\NoResultException $e) {
			//Create the session
			$session = new Session();
			$session->setLocation($data['location']);
			$session->setDate($data['date']);
			$session->setSlot($data['slot']);
			$session->setCreated(new \DateTime('now'));
			$session->setUpdated(new \DateTime('now'));

			//Get short location
			$short = $data['location']->getShort();

			//Get slot
			$slot = $data['slot']->getTitle();

			//Set premium
			$session->setPremium($premium = false);

			//Check if slot is afternoon
			//XXX: premium is stored only for Afternoon and Evening
			if ($slot == 'Afternoon') {
				//Compute premium
				//XXX: a session is considered premium a day off
				$session->setPremium($premium = $this->isPremium($data['date']));
			//Check if slot is evening
			//XXX: premium is stored only for Afternoon and Evening
			} elseif ($slot == 'Evening') {
				//Compute premium
				//XXX: a session is considered premium the eve of a day off
				$session->setPremium($premium = $this->isPremium((clone $data['date'])->add(new \DateInterval('P1D'))));
			//Check if slot is after
			} elseif ($slot == 'After') {
				//Compute premium
				//XXX: a session is considered premium the eve of a day off
				$premium = $this->isPremium((clone $data['date'])->add(new \DateInterval('P1D')));
			}

			//Set default length at 6h
			//XXX: date part will be truncated on save
			$session->setLength(new \DateTime('06:00:00'));

			//Check if admin
			if ($this->isGranted('ROLE_ADMIN')) {
				//Check if morning
				if ($slot == 'Morning') {
					//Set begin at 9h
					$session->setBegin(new \DateTime('09:00:00'));

					//Set length at 5h
					$session->setLength(new \DateTime('05:00:00'));
				//Check if afternoon
				} elseif ($slot == 'Afternoon') {
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
			//Docks => 14h -> 19h | 19h -> 01/02h
			//XXX: remove Garnier from here to switch back to 21h
			} elseif (in_array($short, ['Docks', 'Garnier']) && in_array($slot, ['Afternoon', 'Evening', 'After'])) {
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
			//Garnier => 21h -> 01/02h
			} elseif ($short == 'Garnier' && in_array($slot, ['Evening', 'After'])) {
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
			//Trocadero|Tokyo|Swan|Honore|Orsay => 19h -> 01/02h
			} elseif (in_array($short, ['Trocadero', 'Tokyo', 'Swan', 'Honore', 'Orsay']) && in_array($slot, ['Evening', 'After'])) {
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
			//La Villette => 14h -> 19h
			} elseif ($short == 'Villette' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 5h
				$session->setLength(new \DateTime('05:00:00'));
			//Place Colette => 14h -> 21h
			//TODO: add check here that it's a millegaux account ?
			} elseif ($short == 'Colette' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 7h
				$session->setLength(new \DateTime('07:00:00'));
			//Galerie d'Orléans => 14h -> 18h
			} elseif ($short == 'Orleans' && $slot == 'Afternoon') {
				//Set begin at 14h
				$session->setBegin(new \DateTime('14:00:00'));

				//Set length at 4h
				$session->setLength(new \DateTime('04:00:00'));
			//Combination not supported
			} else {
				//Add error in flash message
				$this->addFlash('error', $this->translator->trans('Session on %date% %location% %slot% not yet supported', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
			}

			//Queue session save
			$manager->persist($session);

			//Flush to get the ids
			#$manager->flush();

			$this->addFlash('notice', $this->translator->trans('Session on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		}

		//Set user
		$user = $this->getUser();

		//Replace with requested user for admin
		if ($this->isGranted('ROLE_ADMIN') && !empty($data['user'])) {
			$user = $this->getDoctrine()->getRepository(User::class)->findOneById($data['user']);
		}

		//Protect application fetching
		try {
			//Retrieve application
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Add warning in flash message
			$this->addFlash('warning', $this->translator->trans('Application on %date% %location% %slot% already exists', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		//Catch no application and session without identifier (not persisted&flushed) cases
		} catch (\Doctrine\ORM\NoResultException|\Doctrine\ORM\ORMInvalidArgumentException $e) {
			//Create the application
			$application = new Application();
			$application->setSession($session);
			$application->setUser($user);
			$application->setCreated(new \DateTime('now'));
			$application->setUpdated(new \DateTime('now'));

			//Refresh session updated field
			$session->setUpdated(new \DateTime('now'));

			//Queue session save
			$manager->persist($session);

			//Queue application save
			$manager->persist($application);

			//Flush to get the ids
			$manager->flush();

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		}
	}

	/**
	 * List all sessions
	 *
	 * @desc Display all sessions with an application or login form
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request = null) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Sessions');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Init context
		$context = [];

		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Create ApplicationType form
			$application = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_application_add'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set admin
				'admin' => $this->isGranted('ROLE_ADMIN'),
				//Set default user to current
				'user' => $this->getUser()->getId(),
				//Set default slot to evening
				//XXX: default to Evening (3)
				'slot' => $doctrine->getRepository(Slot::class)->findOneById(3)
			]);

			//Add form to context
			$context['application'] = $application->createView();
		//Create login form for anonymous
		} elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create ApplicationType form
			$login = $this->createForm('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_user_login'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$context['login'] = $login->createView();
		}

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch calendar
		//TODO: highlight with current session route parameter
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->fetchTranslatedLocationByDatePeriod($this->translator, $period/*, !$this->isGranted('IS_AUTHENTICATED_REMEMBERED')*/);

		//Render the view
		return $this->render('@RapsysAir/session/index.html.twig', ['title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$context+$this->context);
	}

	/**
	 * Display session
	 *
	 * @desc Display session by id with an application or login form
	 *
	 * @param Request $request The request instance
	 * @param int $id The session id
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, $id) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch session
		$session = $doctrine->getRepository(Session::class)->fetchOneById($id);

		//Set section
		$section = $this->translator->trans($session['l_title']);

		//Set title
		$title = $this->translator->trans('Session %id%', ['%id%' => $id]).' - '.$section.' - '.$this->translator->trans($this->config['site']['title']);

		//Init context
		$context = [];

		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Create ApplicationType form
			$application = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_application_add'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set admin
				'admin' => $this->isGranted('ROLE_ADMIN'),
				//Set default user to current
				'user' => $this->getUser()->getId(),
				//Set default slot to current
				'slot' => $this->getDoctrine()->getRepository(Slot::class)->findOneById($session['t_id']),
				//Set default location to current
				'location' => $this->getDoctrine()->getRepository(Location::class)->findOneById($session['l_id']),
			]);

			//Add form to context
			$context['application'] = $application->createView();

			//Set now
			$now = new \DateTime('now');

			//Create SessionEditType form
			$session_edit = $this->createForm('Rapsys\AirBundle\Form\SessionEditType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_session_edit', [ 'id' => $id ]),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set admin
				'admin' => $this->isGranted('ROLE_ADMIN'),
				//Set default user to current
				'user' => $this->getUser()->getId(),
				//Set begin
				'begin' => $session['begin'],
				//Set length
				'length' => $session['length'],
				//Set raincancel
				'raincancel' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['rainfall'] >= 2,
				//Set cancel
				'cancel' => $this->isGranted('ROLE_ADMIN') || in_array($this->getUser()->getId(), explode("\n", $session['sau_id'])),
				//Set modify
				'modify' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_REGULAR'),
				//Set move
				'move' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_SENIOR'),
				//Set session
				'session' => $session['id']
			]);

			//Add form to context
			$context['session_edit'] = $session_edit->createView();
		//Create login form for anonymous
		} elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create ApplicationType form
			$login = $this->createForm('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_user_login'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$context['login'] = $login->createView();
		}

		//Add session in context
		$context['session'] = [
			'id' => $id,
			'date' => $session['date'],
			'begin' => $session['begin'],
			'start' => $session['start'],
			'length' => $session['length'],
			'stop' => $session['stop'],
			'rainfall' => $session['rainfall'] !== null ? $session['rainfall'].' mm' : $session['rainfall'],
			'rainrisk' => $session['rainrisk'] !== null ? ($session['rainrisk']*100).' %' : $session['rainrisk'],
			'realfeel' => $session['realfeel'] !== null ? $session['realfeel'].' °C' : $session['realfeel'],
			'realfeelmin' => $session['realfeelmin'] !== null ? $session['realfeelmin'].' °C' : $session['realfeelmin'],
			'realfeelmax' => $session['realfeelmax'] !== null ? $session['realfeelmax'].' °C' : $session['realfeelmax'],
			'temperature' => $session['temperature'] !== null ? $session['temperature'].' °C' : $session['temperature'],
			'temperaturemin' => $session['temperaturemin'] !== null ? $session['temperaturemin'].' °C' : $session['temperaturemin'],
			'temperaturemax' => $session['temperaturemax'] !== null ? $session['temperaturemax'].' °C' : $session['temperaturemax'],
			'locked' => $session['locked'],
			'created' => $session['created'],
			'updated' => $session['updated'],
			'title' => $this->translator->trans('Session %id%', ['%id%' => $id]),
			'application' => null,
			'location' => [
				'id' => $session['l_id'],
				'at' => $this->translator->trans('at '.$session['l_title']),
				'short' => $this->translator->trans($session['l_short']),
				'title' => $this->translator->trans($session['l_title']),
				'address' => $session['l_address'],
				'zipcode' => $session['l_zipcode'],
				'city' => $session['l_city'],
				'latitude' => $session['l_latitude'],
				'longitude' => $session['l_longitude']
			],
			'slot' => [
				'id' => $session['t_id'],
				'title' => $this->translator->trans($session['t_title'])
			],
			'applications' => null
		];

		//With application
		if (!empty($session['a_id'])) {
			$context['session']['application'] = [
				'user' => [
					'id' => $session['au_id'],
					'title' => $session['au_pseudonym']
				],
				'id' => $session['a_id'],
				'title' => $this->translator->trans('Application %id%', [ '%id%' => $session['a_id'] ]),
			];
		}

		//With applications
		if (!empty($session['sa_id'])) {
			//Extract applications id
			$session['sa_id'] = explode("\n", $session['sa_id']);
			//Extract applications score
			//XXX: score may be null before grant or for bad behaviour, replace NULL with 'NULL' to avoid silent drop in mysql
			$session['sa_score'] = array_map(function($v){return $v==='NULL'?null:$v;}, explode("\n", $session['sa_score']));
			//Extract applications created
			$session['sa_created'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $session['sa_created']));
			//Extract applications updated
			$session['sa_updated'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $session['sa_updated']));
			//Extract applications canceled
			//XXX: canceled is null before cancelation, replace NULL with 'NULL' to avoid silent drop in mysql
			$session['sa_canceled'] = array_map(function($v){return $v==='NULL'?null:new \DateTime($v);}, explode("\n", $session['sa_canceled']));

			//Extract applications user id
			$session['sau_id'] = explode("\n", $session['sau_id']);
			//Extract applications user pseudonym
			$session['sau_pseudonym'] = explode("\n", $session['sau_pseudonym']);

			//Init applications
			$context['session']['applications'] = [];
			foreach($session['sa_id'] as $i => $sa_id) {
				$context['session']['applications'][$sa_id] = [
					'user' => null,
					'score' => $session['sa_score'][$i],
					'created' => $session['sa_created'][$i],
					'updated' => $session['sa_updated'][$i],
					'canceled' => $session['sa_canceled'][$i]
				];
				if (!empty($session['sau_id'][$i])) {
					$context['session']['applications'][$sa_id]['user'] = [
						'id' => $session['sau_id'][$i],
						'title' => $session['sau_pseudonym'][$i]
					];
				}
			}
		}

		//Render the view
		return $this->render('@RapsysAir/session/view.html.twig', ['title' => $title, 'section' => $section]+$context+$this->context);
	}
}
