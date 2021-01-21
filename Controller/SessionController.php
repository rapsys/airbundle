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
		$session = $doctrine->getRepository(Session::class)->fetchOneById($id, $request->getLocale());

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
			'raincancel' => ($this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id']) && $session['rainfall'] >= 2,
			//Set cancel
			'cancel' => $this->isGranted('ROLE_ADMIN') || in_array($this->getUser()->getId(), explode("\n", $session['sau_id'])),
			//Set modify
			'modify' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_REGULAR'),
			//Set move
			'move' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_SENIOR'),
			//Set attribute
			'attribute' => $this->isGranted('ROLE_ADMIN') && $session['locked'] === null,
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

		//Set canceled time at start minus one day
		$canceled = (clone $session->getStart())->sub(new \DateInterval('P1D'));

		//Set action
		$action = [
			'raincancel' => $form->has('raincancel') && $form->get('raincancel')->isClicked(),
			'modify' => $form->has('modify') && $form->get('modify')->isClicked(),
			'move' => $form->has('move') && $form->get('move')->isClicked(),
			'cancel' => $form->has('cancel') && $form->get('cancel')->isClicked(),
			'forcecancel' => $form->has('forcecancel') && $form->get('forcecancel')->isClicked(),
			'attribute' => $form->has('attribute') && $form->get('attribute')->isClicked(),
			'autoattribute' => $form->has('autoattribute') && $form->get('autoattribute')->isClicked(),
			'lock' => $form->has('lock') && $form->get('lock')->isClicked(),
		];

		//With raincancel and application and (rainfall or admin)
		if ($action['raincancel'] && ($application = $session->getApplication()) && ($session->getRainfall() >= 2 || $this->isGranted('ROLE_ADMIN'))) {
			//Cancel application at start minus one day
			$application->setCanceled($canceled);

			//Update time
			$application->setUpdated($datetime);

			//Insufficient rainfall
			//XXX: is admin
			if ($session->getRainfall() < 2) {
				//Set score
				//XXX: magic cheat score 42
				$application->setScore(42);
			}

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
		//With modify
		} elseif ($action['modify']) {
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
		} elseif ($action['move']) {
			//Set location
			$session->setLocation($doctrine->getRepository(Location::class)->findOneById($data['location']));

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		//With cancel or forcecancel
		} elseif ($action['cancel'] || $action['forcecancel']) {
			//Get application
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Not already canceled
			if ($application->getCanceled() === null) {
				//Cancel application
				$application->setCanceled($datetime);

				//Check if application is session application and (canceled 24h before start or forcecancel (as admin))
				#if ($session->getApplication() == $application && ($datetime < $canceled || $action['forcecancel'])) {
				if ($session->getApplication() == $application && $action['forcecancel']) {
					//Set score
					//XXX: magic cheat score 42
					$application->setScore(42);

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
		} elseif ($action['attribute']) {
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
		} elseif ($action['autoattribute']) {
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
				$this->addFlash('notice', $this->translator->trans('Session %id% auto attributed', ['%id%' => $id]));
			//No application
			} else {
				//Add warning in flash message
				$this->addFlash('warning', $this->translator->trans('Session %id% not auto attributed', ['%id%' => $id]));
			}
		//With lock
		} elseif ($action['lock']) {
			//Already locked
			if ($session->getLocked() !== null) {
				//Set uncanceled
				$canceled = null;

				//Unlock session
				$session->setLocked(null);
			//Not locked
			} else {
				//Get application
				if ($application = $session->getApplication()) {
					//Set score
					//XXX: magic cheat score 42
					$application->setScore(42);

					//Update time
					$application->setUpdated($datetime);

					//Queue application save
					$manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
				}

				//Unattribute session
				$session->setApplication(null);

				//Lock session
				$session->setLocked($datetime);
			}

#			//Get applications
#			$applications = $doctrine->getRepository(Application::class)->findBySession($session);
#
#			//Not empty
#			if (!empty($applications)) {
#				//Iterate on each applications
#				foreach($applications as $application) {
#					//Cancel application
#					$application->setCanceled($canceled);
#
#					//Update time
#					$application->setUpdated($datetime);
#
#					//Queue application save
#					$manager->persist($application);
#
#					//Add notice in flash message
#					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
#				}
#			}

			//Update time
			$session->setUpdated($datetime);

			//Queue session save
			$manager->persist($session);

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
		//Unknown action
		} else {
			//Add warning in flash message
			$this->addFlash('warning', $this->translator->trans('Session %id% not updated', ['%id%' => $id]));
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
				return $this->redirectToRoute($name, $route);
			//No route matched
			} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air_session_view', ['id' => $id]);
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
	public function index(Request $request) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Sessions');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air session list');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('sessions'),
			$this->translator->trans('session list'),
			$this->translator->trans('listing'),
			$this->translator->trans('Libre Air')
		];

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

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
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 4 week':'Monday this week + 2 week'
			)
		);

		//Fetch calendar
		//TODO: highlight with current session route parameter
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

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
		if (empty($session = $doctrine->getRepository(Session::class)->fetchOneById($id, $request->getLocale()))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find session: %id%', ['%id%' => $id]));
		}

		//Set section
		$section = $this->translator->trans($session['l_title']);

		//Set localization date formater
		$intl = new \IntlDateFormatter($request->getLocale(), \IntlDateFormatter::GREGORIAN, \IntlDateFormatter::SHORT);

		//Set description
		$this->context['description'] = $this->translator->trans('Outdoor Argentine Tango session the %date%', [ '%date%' => $intl->format($session['start']) ]);

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
		];

		//With granted session
		if (!empty($session['au_id'])) {
			$this->context['keywords'][0] = $session['au_pseudonym'];
		}
		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section.' - '.$this->translator->trans(!empty($session['au_id'])?'Session %id% by %pseudonym%':'Session %id%', ['%id%' => $id, '%pseudonym%' => $session['au_pseudonym']]);

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
				'raincancel' => ($this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id']) && $session['rainfall'] >= 2,
				//Set cancel
				'cancel' => $this->isGranted('ROLE_ADMIN') || in_array($this->getUser()->getId(), explode("\n", $session['sau_id'])),
				//Set modify
				'modify' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_REGULAR'),
				//Set move
				'move' => $this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $session['au_id'] && $session['stop'] >= $now && $this->isGranted('ROLE_SENIOR'),
				//Set attribute
				'attribute' => $this->isGranted('ROLE_ADMIN') && $session['locked'] === null,
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
			'snippet' => [
				'id' => $session['p_id'],
				'description' => $session['p_description'],
				'class' => $session['p_class'],
				'contact' => $session['p_contact'],
				'donate' => $session['p_donate'],
				'link' => $session['p_link'],
				'social' => $session['p_social']
			],
			'applications' => null
		];

		//With application
		if (!empty($session['a_id'])) {
			$context['session']['application'] = [
				'user' => [
					'id' => $session['au_id'],
					'by' => $this->translator->trans('by %pseudonym%', [ '%pseudonym%' => $session['au_pseudonym'] ]),
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

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 4 week':'Monday this week + 2 week'
			)
		);

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period, $session['au_id']);

		//Render the view
		return $this->render('@RapsysAir/session/view.html.twig', ['title' => $title, 'section' => $section, 'locations' => $locations]+$context+$this->context);
	}
}
