<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

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
	public function edit(Request $request, $id): Response {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_GUEST', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));

		//Reject non post requests
		if (!$request->isMethod('POST')) {
			throw new \RuntimeException('Request method MUST be POST');
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Set locale
		$locale = $request->getLocale();

		//Fetch session
		$session = $doctrine->getRepository(Session::class)->fetchOneById($id, $locale);

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

		//Create SessionType form
		$form = $this->createForm('Rapsys\AirBundle\Form\SessionType', null, [
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
			//Set page
			$this->context['page']['title'] = $this->translator->trans(!empty($session['au_id'])?'Session %id% by %pseudonym%':'Session %id%', ['%id%' => $id, '%pseudonym%' => $session['au_pseudonym']]);

			//Set facebook title
			$this->context['ogps']['title'] = $this->context['page']['title'].' '.$this->translator->trans('at '.$session['l_title']);

			//Set section
			$this->context['page']['section'] = $this->translator->trans($session['l_title']);

			//Set localization date formater
			$intlDate = new \IntlDateFormatter($locale, \IntlDateFormatter::TRADITIONAL, \IntlDateFormatter::NONE);

			//Set localization time formater
			$intlTime = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);

			//Set facebook image
			$this->context['facebook'] += [
				'texts' => [
					$session['au_pseudonym'] => [
						'font' => 'irishgrover',
						'size' => 110
					],
					ucfirst($intlDate->format($session['start']))."\n".$this->translator->trans('From %start% to %stop%', ['%start%' => $intlTime->format($session['start']), '%stop%' => $intlTime->format($session['stop'])]) => [
						'align' => 'left'
					],
					$this->translator->trans('at '.$session['l_title']) => [
						'align' => 'right',
						'font' => 'labelleaurore',
						'size' => 75
					]
				],
				'updated' => $session['updated']->format('U')
			];

			//Add session in context
			$this->context['session'] = [
				'id' => $id,
				'title' => $this->translator->trans('Session %id%', ['%id%' => $id]),
				'location' => [
					'id' => $session['l_id'],
					'at' => $this->translator->trans('at '.$session['l_title'])
				]
			];

			//Render the view
			return $this->render('@RapsysAir/session/edit.html.twig', ['form' => $form->createView()]+$this->context);
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
	public function index(Request $request): Response {
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

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 3 week':'Monday this week + 2 week'
			)
		);

		//Fetch calendar
		//TODO: highlight with current session route parameter
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/session/index.html.twig', ['title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
	}

	/**
	 * List all sessions for tango argentin
	 *
	 * @desc Display all sessions in tango argentin json format
	 *
	 * @todo Drop it if unused by tangoargentin ???
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function tangoargentin(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('today'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime('+2 week')
		);

		//Retrieve events to update
		$sessions = $doctrine->getRepository(Session::class)->fetchAllByDatePeriod($period, $request->getLocale());

		//Init return array
		$ret = [];

		//Iterate on sessions
		foreach($sessions as $sessionId => $session) {
			//Set title
			$title = $session['au_pseudonym'].' '.$this->translator->trans('at '.$session['l_short']);
			//Use Transliterator if available
			if (class_exists('Transliterator')) {
				$trans = \Transliterator::create('Any-Latin; Latin-ASCII; Upper()');
				$title = $trans->transliterate($title);
			} else {
				$title = strtoupper($title);
			}
			//Store session data
			$ret[$sessionId] = [
				'start' => $session['start']->format(\DateTime::ISO8601),
				'stop' => $session['start']->format(\DateTime::ISO8601),
				'title' => $title,
				'short' => $session['p_short'],
				'rate' => is_null($session['p_rate'])?'Au chapeau':$session['p_rate'].' euro'.($session['p_rate']>1?'s':''),
				'location' => implode(' ', [$session['l_address'], $session['l_zipcode'], $session['l_city']]),
				'status' => (empty($session['a_canceled']) && empty($session['locked']))?'confirmed':'cancelled',
				'updated' => $session['updated']->format(\DateTime::ISO8601),
				'organizer' => $session['au_forename'],
				'website' => $this->router->generate('rapsys_air_session_view', ['id' => $sessionId], UrlGeneratorInterface::ABSOLUTE_URL)
			];
		}

		//Set response
		$response = new Response(json_encode($ret));

		//Set header
		$response->headers->set('Content-Type', 'application/json');

		//Send response
		return $response;
	}

	/**
	 * Display session
	 *
	 * @todo XXX: TODO: add <link rel="prev|next" for sessions or classes ? />
	 * @todo XXX: TODO: like described in: https://www.alsacreations.com/article/lire/1400-attribut-rel-relations.html#xnf-rel-attribute
	 * @todo XXX: TODO: or here: http://microformats.org/wiki/existing-rel-values#HTML5_link_type_extensions
	 *
	 * @desc Display session by id with an application or login form
	 *
	 * @param Request $request The request instance
	 * @param int $id The session id
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, $id): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set locale
		$locale = $request->getLocale();

		//Fetch session
		if (empty($session = $doctrine->getRepository(Session::class)->fetchOneById($id, $locale))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find session: %id%', ['%id%' => $id]));
		}

		//Create response
		$response = new Response();

		//Set etag
		$response->setEtag(md5(serialize($session)));

		//With logged user
		if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Extract applications updated
			$session['sa_updated'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $session['sa_updated']));

			//Get last modified
			$lastModified = max(array_merge([$session['updated'], $session['l_updated'], $session['t_updated'], $session['p_updated']], $session['sa_updated']));

			//Set last modified
			$response->setLastModified($lastModified);

			//Set as public
			$response->setPublic();

			//Without role and modification
			if ($response->isNotModified($request)) {
				//Return 304 response
				return $response;
			}
		}

		//Set localization date formater
		$intl = new \IntlDateFormatter($locale, \IntlDateFormatter::GREGORIAN, \IntlDateFormatter::SHORT);

		//Set section
		$this->context['page']['section'] = $this->translator->trans($session['l_title']);

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Outdoor Argentine Tango session the %date%', [ '%date%' => $intl->format($session['start']) ]);

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
		];

		//Set localization date formater
		$intlDate = new \IntlDateFormatter($locale, \IntlDateFormatter::TRADITIONAL, \IntlDateFormatter::NONE);

		//Set localization time formater
		$intlTime = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);

		//Set facebook image
		$this->context['facebook'] = [
			'texts' => [
				$session['au_pseudonym'] => [
					'font' => 'irishgrover',
					'size' => 110
				],
				ucfirst($intlDate->format($session['start']))."\n".$this->translator->trans('From %start% to %stop%', ['%start%' => $intlTime->format($session['start']), '%stop%' => $intlTime->format($session['stop'])]) => [
					'align' => 'left'
				],
				$this->translator->trans('at '.$session['l_title']) => [
					'align' => 'right',
					'font' => 'labelleaurore',
					'size' => 75
				]
			],
			'updated' => $session['updated']->format('U')
		]+$this->context['facebook'];

		//With granted session
		if (!empty($session['au_id'])) {
			$this->context['keywords'][0] = $session['au_pseudonym'];
		}

		//Set page
		$this->context['page']['title'] = $this->translator->trans(!empty($session['au_id'])?'Session %id% by %pseudonym%':'Session %id%', ['%id%' => $id, '%pseudonym%' => $session['au_pseudonym']]);

		//Set facebook title
		$this->context['ogps']['title'] = $this->context['page']['title'].' '.$this->translator->trans('at '.$session['l_title']);

		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Create ApplicationType form
			$applicationForm = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
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
			$this->context['forms']['application'] = $applicationForm->createView();

			//Set now
			$now = new \DateTime('now');

			//Create SessionType form
			$sessionForm = $this->createForm('Rapsys\AirBundle\Form\SessionType', null, [
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
			$this->context['forms']['session'] = $sessionForm->createView();
		}

		//Add session in context
		$this->context['session'] = [
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
				'profile' => $session['p_profile']
			],
			'applications' => null
		];

		//With application
		if (!empty($session['a_id'])) {
			$this->context['session']['application'] = [
				'user' => [
					'id' => $session['au_id'],
					'by' => $this->translator->trans('by %pseudonym%', [ '%pseudonym%' => $session['au_pseudonym'] ]),
					'title' => $session['au_pseudonym']
				],
				'id' => $session['a_id'],
				'canceled' => $session['a_canceled'],
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
			//XXX: done earlied when computing last modified
			#$session['sa_updated'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $session['sa_updated']));
			//Extract applications canceled
			//XXX: canceled is null before cancelation, replace NULL with 'NULL' to avoid silent drop in mysql
			$session['sa_canceled'] = array_map(function($v){return $v==='NULL'?null:new \DateTime($v);}, explode("\n", $session['sa_canceled']));

			//Extract applications user id
			$session['sau_id'] = explode("\n", $session['sau_id']);
			//Extract applications user pseudonym
			$session['sau_pseudonym'] = explode("\n", $session['sau_pseudonym']);

			//Init applications
			$this->context['session']['applications'] = [];
			foreach($session['sa_id'] as $i => $sa_id) {
				$this->context['session']['applications'][$sa_id] = [
					'user' => null,
					'score' => $session['sa_score'][$i],
					'created' => $session['sa_created'][$i],
					'updated' => $session['sa_updated'][$i],
					'canceled' => $session['sa_canceled'][$i]
				];
				if (!empty($session['sau_id'][$i])) {
					$this->context['session']['applications'][$sa_id]['user'] = [
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
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 3 week':'Monday this week + 2 week'
			)
		);

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period, $session['au_id']);

		//Render the view
		return $this->render('@RapsysAir/session/view.html.twig', ['locations' => $locations]+$this->context, $response);
	}
}
