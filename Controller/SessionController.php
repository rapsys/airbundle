<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class SessionController extends DefaultController {
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
			$session['sa_canceled'] = array_map(function($v){return $v==='NULL'?null:$v;}, explode("\n", $session['sa_canceled']));

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
