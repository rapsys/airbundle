<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Application;

class ApplicationController extends DefaultController {
	/**
	 * Add application
	 *
	 * @desc Persist application and all required dependencies in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function add(Request $request) {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_GUEST', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));

		//Create ApplicationType form
		$form = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
			//Set the action
			'action' => $this->generateUrl('rapsys_air_application_add'),
			//Set the form attribute
			#'attr' => [ 'class' => 'col' ],
			//Set admin
			'admin' => $this->isGranted('ROLE_ADMIN'),
			//Set default user to current
			'user' => $this->getUser()->getId(),
			//Set default slot to evening
			//XXX: default to Evening (3)
			'slot' => $this->getDoctrine()->getRepository(Slot::class)->findOneById(3)
		]);

		//Reject non post requests
		if (!$request->isMethod('POST')) {
			throw new \RuntimeException('Request method MUST be POST');
		}

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Application Add');

			//Set title
			$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

			//Render the view
			return $this->render('@RapsysAir/application/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form]+$this->context);
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get manager
		$manager = $doctrine->getManager();

		//Get data
		$data = $form->getData();

		//Protect session fetching
		try {
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

			//Add notice in flash message
			//TODO: set warning about application already exists bla bla bla...
			#$this->addFlash('notice', $this->translator->trans('Application request the %date% for %location% on the slot %slot% saved', ['%location%' => $data['location']->getTitle(), '%slot%' => $data['slot']->getTitle(), '%date%' => $data['date']->format('Y-m-d')]));

			//Add error message to mail field
			#$form->get('slot')->addError(new FormError($this->translator->trans('Application already exists')));

			//TODO: redirect anyway on uri with application highlighted
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
			#$manager->flush();

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		}
		
		//Try unshort return field
		if (
			!empty($data['return']) &&
			($unshort = $this->slugger->unshort($data['return'])) &&
			($route = json_decode($unshort, true)) !== null
		) {
			$return = $this->generateUrl($route['_route'], ['session' => $session->getId()?:1]+$route['_route_params']);
		}

		//XXX: Debug
		header('Content-Type: text/plain');

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
				var_dump($this->router);
				exit;
				var_dump($path = '/location');
				var_dump($this->router->match($path));
				var_dump($path = '/fr/emplacement');
				exit;
				var_dump($this->router->match());
				exit;
				var_dump($path);
				var_dump($query);
				exit;
				//Retrieve route matching path
				$route = $this->router->match($path);
				var_dump($route);
				exit;

				//Verify that it differ from current one
				if (($name = $route['_route']) == $logout) {
					throw new ResourceNotFoundException('Identical referer and logout route');
				}

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller']);

				//Generate url
				$url = $this->router->generate($name, $route);
			//No route matched
			} catch(ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}
		var_dump($request->headers->get('referer'));
		#var_dump($request->get('_route'));

		var_dump($return);
		exit;

		//Fetch slugger helper
		$slugger = $this->get('rapsys.slugger');

		var_dump($short = $slugger->short(json_encode(['_route' => $request->get('_route'), '_route_params' => $request->get('_route_params')])));
		$short[12] = 'T';
		var_dump($ret = json_decode($slugger->unshort($short), true));
		var_dump($ret);
		var_dump($this->generateUrl($ret['_route'], $ret['_route_params']));
		#var_dump(json_decode($slugger->unshort($data['return'])));
		#var_dump($application->getId());
		exit;

		//Init application
		$application = false;

		//Protect application fetching
		try {
			//TODO: handle admin case where we provide a user in extra
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $this->getUser());

			//Add error message to mail field
			$form->get('slot')->addError(new FormError($this->translator->trans('Application already exists')));
		//Catch no application cases
		//XXX: combine these catch when php 7.1 is available
		} catch (\Doctrine\ORM\NoResultException $e) {
		//Catch invalid argument because session is not already persisted
		} catch(\Doctrine\ORM\ORMInvalidArgumentException $e) {
		}

		//Create new application if none found
		if (!$application) {
			//Create the application
			$application = new Application();
			$application->setSession($session);
			//TODO: handle admin case where we provide a user in extra
			$application->setUser($this->getUser());
			$application->setCreated(new \DateTime('now'));
			$application->setUpdated(new \DateTime('now'));
			$manager->persist($application);

			//Flush to get the ids
			$manager->flush();

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application request the %date% for %location% on the slot %slot% saved', ['%location%' => $data['location']->getTitle(), '%slot%' => $data['slot']->getTitle(), '%date%' => $data['date']->format('Y-m-d')]));

			//Redirect to cleanup the form
			return $this->redirectToRoute('rapsys_air_admin');
		}
	}

	function test(Request $request) {

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
	}
}
