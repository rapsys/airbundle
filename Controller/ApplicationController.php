<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
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

		//Reject non post requests
		if (!$request->isMethod('POST')) {
			throw new \RuntimeException('Request method MUST be POST');
		}

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

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Application add');

			//Set title
			$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

			//Render the view
			return $this->render('@RapsysAir/application/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
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

				//Set section
				$section = $this->translator->trans('Application add');

				//Set title
				$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

				//Render the view
				return $this->render('@RapsysAir/application/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
			}

			//Check if admin
			if (!$this->isGranted('ROLE_ADMIN') && $session->getStart() < new \DateTime('00:00:00')) {
				//Add error in flash message
				$this->addFlash('error', $this->translator->trans('Session in the past on %date% %location% %slot% not yet supported', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));

				//Set section
				$section = $this->translator->trans('Application add');

				//Set title
				$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

				//Render the view
				return $this->render('@RapsysAir/application/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
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
				if ($name == 'rapsys_air_session_view' && !empty($route['id'])) {
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
			} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air', ['session' => $session->getId()]);
	}

	/**
	 * Compute eastern for selected year
	 *
	 * @param int $year The eastern year
	 *
	 * @return DateTime The eastern date
	 */
	function getEastern($year) {
		//Set static
		static $data = null;
		//Check if already computed
		if (isset($data[$year])) {
			//Return computed eastern
			return $data[$year];
		//Check if data is null
		} elseif (is_null($data)) {
			//Init data array
			$data = [];
		}
		$d = (19 * ($year % 19) + 24) % 30;
		$e = (2 * ($year % 4) + 4 * ($year % 7) + 6 * $d + 5) % 7;

		$day = 22 + $d + $e;
		$month = 3;

		if ($day > 31) {
			$day = $d + $e - 9;
			$month = 4;
		} elseif ($d == 29 && $e == 6) {
			$day = 10;
			$month = 4;
		} elseif ($d == 28 && $e == 6) {
			$day = 18;
			$month = 4;
		}

		//Store eastern in data
		return ($data[$year] = new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day)));
	}

	/**
	 * Check if date is a premium day
	 *
	 * @desc Consider as premium a day off
	 *
	 * @param DateTime $date The date to check
	 * @return bool Whether the date is off or not
	 */
	function isPremium($date) {
		//Get day number
		$w = $date->format('w');

		//Check if weekend day
		if ($w == 0 || $w == 6) {
			//Date is weekend day
			return true;
		}

		//Get date day
		$d = $date->format('d');

		//Get date month
		$m = $date->format('m');

		//Check if fixed holiday
		if (
			//Check if 1st january
			($d == 1 && $m == 1) ||
			//Check if 1st may
			($d == 1 && $m == 5) ||
			//Check if 8st may
			($d == 8 && $m == 5) ||
			//Check if 14st july
			($d == 14 && $m == 7) ||
			//Check if 15st august
			($d == 15 && $m == 8) ||
			//Check if 1st november
			($d == 1 && $m == 11) ||
			//Check if 11st november
			($d == 11 && $m == 11) ||
			//Check if 25st december
			($d == 25 && $m == 12)
		) {
			//Date is a fixed holiday
			return true;
		}

		//Get eastern
		$eastern = $this->getEastern($date->format('Y'));

		//Check dynamic holidays
		if (
			(clone $eastern)->add(new \DateInterval('P1D')) == $date ||
			(clone $eastern)->add(new \DateInterval('P39D')) == $date ||
			(clone $eastern)->add(new \DateInterval('P50D')) == $date
		) {
			//Date is a dynamic holiday
			return true;
		}

		//Date is not a holiday and week day
		return false;
	}
}
