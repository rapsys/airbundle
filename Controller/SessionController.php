<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class SessionController extends DefaultController {
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
		//TODO: genereate a custom request to fetch everything in a single request ???
		$session = $doctrine->getRepository(Session::class)->findOneById($id);

		//Fetch session
		//TODO: genereate a custom request to fetch everything in a single request ???
		$location = $session->getLocation(); #$doctrine->getRepository(Location::class)->findOneBySession($session);

		//Set section
		//TODO: replace with $session['location']['title']
		$section = $this->translator->trans($location);

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

		//Extract session date
		$sessionDate = $session->getDate();

		//Init session begin and end
		$sessionBegin = $sessionEnd = null;

		//Check session begin and end availability
		if (($sessionBegin = $session->getBegin()) && ($sessionLength = $session->getLength())) {
			$sessionBegin = new \DateTime($sessionDate->format('Y-m-d')."\t".$sessionBegin->format('H:i:sP'));
			#$sessionEnd = (new \DateTime($sessionDate->format('Y-m-d')."\t".$sessionBegin->format('H:i:sP')))->add(new \DateInterval($sessionLength->format('\P\TH\Hi\Ms\S')));
			$sessionEnd = (clone $sessionBegin)->add(new \DateInterval($sessionLength->format('\P\TH\Hi\Ms\S')));
		}

		//Add session in context
		$context['session'] = [
			'id' => ($sessionId = $session->getId()),
			'date' => $sessionDate,
			'begin' => $sessionBegin,
			'end' => $sessionEnd,
			#'begin' => new \DateTime($session->getDate()->format('Y-m-d')."\t".$session->getBegin()->format('H:i:sP')),
			#'end' => (new \DateTime($session->getDate()->format('Y-m-d')."\t".$session->getBegin()->format('H:i:sP')))->add(new \DateInterval($session->getLength()->format('\P\TH\Hi\Ms\S'))),
			#'length' => $session->getLength(),
			'created' => $session->getCreated(),
			'updated' => $session->getUpdated(),
			'title' => $this->translator->trans('Session %id%', ['%id%' => $sessionId]),
			'application' => null,
			'location' => [
				'id' => ($location = $session->getLocation())->getId(),
				'title' => $this->translator->trans($location),
			],
			'slot' => [
				'id' => ($slot = $session->getSlot())->getId(),
				'title' => $this->translator->trans($slot),
			],
			'applications' => null,
		];

		if ($application = $session->getApplication()) {
			$context['session']['application'] = [
				'user' => [
					'id' => ($user = $application->getUser())->getId(),
					'title' => (string) $user->getPseudonym(),
				],
				'id' => ($applicationId = $application->getId()),
				'title' => $this->translator->trans('Application %id%', [ '%id%' => $applicationId ]),
			];
		}

		if ($applications = $session->getApplications()) {
			$context['session']['applications'] = [];
			foreach($applications as $application) {
				$context['session']['applications'][$applicationId = $application->getId()] = [
					'user' => null,
					'created' => $application->getCreated(),
					'updated' => $application->getUpdated(),
				];
				if ($user = $application->getUser()) {
					$context['session']['applications'][$applicationId]['user'] = [
						'id' => $user->getId(),
						'title' => (string) $user->getPseudonym(),
					];
				}
			}
		}

		//Add location in context
		#$context['location'] = [
		#	'id' => $location->getId(),
		#	'title' => $location->getTitle(),
		#];

		//Render the view
		return $this->render('@RapsysAir/session/view.html.twig', ['title' => $title, 'section' => $section]+$context+$this->context);
	}
}
