<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Application;
use Symfony\Component\Form\FormError;

class DefaultController extends Controller {
	public function contactAction(Request $request) {
		//Get translator
		$trans = $this->get('translator');

		//Set section
		$section = $trans->trans('Contact');

		//Set title
		$title = $section.' - '.$trans->trans($this->getParameter('rapsys_air.title'));

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', null, [
			// To set the action use $this->generateUrl('route_identifier')
			'action' => $this->generateUrl('rapsys_air_contact'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Get contact name
				$contactName = $this->getParameter('rapsys_air.contact_name');

				//Get contact mail
				$contactMail = $this->getParameter('rapsys_air.contact_mail');

				//Get logo
				$logo = $this->getParameter('rapsys_air.logo');

				//Get title
				$title = $trans->trans($this->getParameter('rapsys_air.title'));

				//Get subtitle
				$subtitle = $trans->trans('Hi,').' '.$contactName;

				$message = \Swift_Message::newInstance()
					->setSubject($data['subject'])
					->setFrom([$data['mail'] => $data['name']])
					->setTo([$contactMail => $contactName])
					->setBody($data['message'])
					->addPart(
						$this->renderView(
							'@RapsysAir/mail/generic.html.twig',
							[
								'logo' => $logo,
								'title' => $title,
								'subtitle' => $subtitle,
								'home' => $this->get('router')->generate('rapsys_air_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL),
								'subject' => $data['subject'],
								'contact_name' => $contactName,
								'message' => strip_tags($data['message'])
							]
						),
						'text/html'
					);
				//Send message
				if ($this->get('mailer')->send($message)) {
					//Redirect to cleanup the form
					return $this->redirectToRoute('rapsys_air_contact', ['sent' => 1]);
				}
			}
		}

		//Render template
		return $this->render('@RapsysAir/form/contact.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView(), 'sent' => $request->query->get('sent', 0)]);
	}

	public function indexAction() {
		//Get translator
		$trans = $this->get('translator');

		//Set section
		$section = $trans->trans('Index');

		//Set title
		$title = $section.' - '.$trans->trans($this->getParameter('rapsys_air.title'));

		return $this->render('@RapsysAir/page/index.html.twig', ['title' => $title, 'section' => $section]);
	}

	public function adminAction(Request $request) {
		$this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Unable to access this page!');

		//Get translator
		$trans = $this->get('translator');

		//Set section
		$section = $trans->trans('Admin');

		//Set title
		$title = $section.' - '.$trans->trans($this->getParameter('rapsys_air.title'));

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
			// To set the action use $this->generateUrl('route_identifier')
			'action' => $this->generateUrl('rapsys_air_admin'),
			'method' => 'POST',
			'attr' => [ 'class' => 'form_col' ]
		]);

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Handle request
		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Get manager
				$manager = $doctrine->getManager();

				//Protect session fetching
				try {
					$session = $doctrine->getRepository(Session::class)->findOneByLocationSlotDate($data['location'], $data['slot'], $data['date']);
				//Catch no session case
				} catch (\Doctrine\ORM\NoResultException $e) {
					//Create the session
					$session = new Session();
					$session->setLocation($data['location']);
					$session->setSlot($data['slot']);
					$session->setDate($data['date']);
					$session->setCreated(new \DateTime('now'));
					$session->setUpdated(new \DateTime('now'));
					$manager->persist($session);
					//Flush to get the ids
					#$manager->flush();
				}

				//Init application
				$application = false;

				//Protect application fetching
				try {
					//TODO: handle admin case where we provide a user in extra
					$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $this->getUser());

					//Add error message to mail field
					$form->get('slot')->addError(new FormError($trans->trans('Application already exists')));
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
					$this->addFlash('notice', $trans->trans('Application request the %date% for %location% on the slot %slot% saved', ['%location%' => $data['location']->getTitle(), '%slot%' => $data['slot']->getTitle(), '%date%' => $data['date']->format('Y-m-d')]));

					//Redirect to cleanup the form
					return $this->redirectToRoute('rapsys_air_admin');
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
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch sessions
		$sessions = $doctrine->getRepository(Session::class)->findByDatePeriod($period);

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

		return $this->render('@RapsysAir/admin/index.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView(), 'calendar' => $calendar]);
	}

	public function sessionAction(Request $request, $id) {
		/*header('Content-Type: text/plain');
		var_dump($calendar);
		exit;*/

		//Get translator
		$trans = $this->get('translator');

		//Set section
		$section = $trans->trans('Session %id%', ['%id%' => $id]);

		//Set title
		$title = $section.' - '.$trans->trans($this->getParameter('rapsys_air.title'));

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		/*$form = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
			// To set the action use $this->generateUrl('route_identifier')
			'action' => $this->generateUrl('rapsys_air_admin'),
			'method' => 'POST',
			'attr' => [ 'class' => 'form_col' ]
		]);*/

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Fetch session
		$session = $doctrine->getRepository(Session::class)->findOneById($id);

		return $this->render('@RapsysAir/admin/session.html.twig', ['title' => $title, 'section' => $section, /*'form' => $form->createView(),*/ 'session' => $session]);
	}
}
