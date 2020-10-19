<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class LocationController extends DefaultController {
	/**
	 * List all locations
	 *
	 * @desc Display all sessions by location with an application or login form
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request = null) {
		//Set section
		$section = $this->translator->trans('Locations');

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
				'slot' => $this->getDoctrine()->getRepository(Slot::class)->findOneById(3)
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

		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch sessions
		$sessions = $doctrine->getRepository(Session::class)->findAllByDatePeriod($period);

		//Init calendar
		$calendar = [];

		//Init month
		$month = null;

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $date->format('d'),
				'class' => [],
				'sessions' => []
			];
			//Append month for first day of month
			if ($month != $date->format('m')) {
				$month = $date->format('m');
				$calendar[$Ymd]['title'] .= '/'.$month;
			}
			//Deal with today
			if ($date->format('U') == ($today = strtotime('today'))) {
				$calendar[$Ymd]['title'] .= '/'.$month;
				$calendar[$Ymd]['current'] = true;
				$calendar[$Ymd]['class'][] =  'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] =  'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				$calendar[$Ymd]['class'][] =  'next';
			}
			//Iterate on each session to find the one of the day
			foreach($sessions as $session) {
				if (($sessionYmd = $session->getDate()->format('Ymd')) == $Ymd) {
					//Count number of application
					$count = count($session->getApplications());

					//Compute classes
					$class = [];
					if ($session->getApplication()) {
						$class[] = 'granted';
					} elseif ($count == 0) {
						$class[] = 'orphaned';
					} elseif ($count > 1) {
						$class[] = 'disputed';
					} else {
						$class[] = 'pending';
					}

					//Add the session
					$calendar[$Ymd]['sessions'][$session->getSlot()->getId().$session->getLocation()->getId()] = [
						'id' => $session->getId(),
						'title' => ($count > 1?'['.$count.'] ':'').$session->getSlot()->getTitle().' '.$session->getLocation()->getTitle(),
						'class' => $class
					];
				}
			}

			//Sort sessions
			ksort($calendar[$Ymd]['sessions']);
		}

		//Render the view
		return $this->render('@RapsysAir/location/index.html.twig', ['title' => $title, 'section' => $section, 'calendar' => $calendar]+$context+$this->context);
	}

	public function view(Request $request, $id) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch location
		$location = $doctrine->getRepository(Location::class)->findOneById($id);

		//Set section
		$section = $this->translator->trans($location);

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
				'slot' => $this->getDoctrine()->getRepository(Slot::class)->findOneById(3)
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

		//Fetch sessions
		$sessions = $doctrine->getRepository(Session::class)->findAllByLocationDatePeriod($location, $period);

		//Init calendar
		$calendar = [];
		
		//Init month
		$month = null;

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $date->format('d'),
				'class' => [],
				'sessions' => []
			];
			//Append month for first day of month
			if ($month != $date->format('m')) {
				$month = $date->format('m');
				$calendar[$Ymd]['title'] .= '/'.$month;
			}
			//Deal with today
			if ($date->format('U') == ($today = strtotime('today'))) {
				$calendar[$Ymd]['title'] .= '/'.$month;
				$calendar[$Ymd]['current'] = true;
				$calendar[$Ymd]['class'][] =  'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] =  'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				$calendar[$Ymd]['class'][] =  'next';
			}
			//Iterate on each session to find the one of the day
			foreach($sessions as $session) {
				if (($sessionYmd = $session->getDate()->format('Ymd')) == $Ymd) {
					//Count number of application
					$count = count($session->getApplications());

					//Compute classes
					$class = [];
					if ($session->getApplication()) {
						$class[] = 'granted';
					} elseif ($count == 0) {
						$class[] = 'orphaned';
					} elseif ($count > 1) {
						$class[] = 'disputed';
					} else {
						$class[] = 'pending';
					}

					//Add the session
					$calendar[$Ymd]['sessions'][$session->getSlot()->getId().$session->getLocation()->getId()] = [
						'id' => $session->getId(),
						'title' => ($count > 1?'['.$count.'] ':'').$session->getSlot()->getTitle().' '.$session->getLocation()->getTitle(),
						'class' => $class
					];
				}
			}

			//Sort sessions
			ksort($calendar[$Ymd]['sessions']);
		}

		//Render the view
		return $this->render('@RapsysAir/location/index.html.twig', ['title' => $title, 'section' => $section, 'calendar' => $calendar]+$context+$this->context);
	}
}
