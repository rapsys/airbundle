<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\User;

class UserController extends DefaultController {
	/**
	 * List all sessions for the user
	 *
	 * @desc Display all sessions for the user with an application or login form
	 *
	 * @param Request $request The request instance
	 * @param int $id The user id
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, $id) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch user
		$user = $doctrine->getRepository(User::class)->findOneById($id);

		//Set section
		$section = $user->getPseudonym();

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
		$calendar = $doctrine->getRepository(Session::class)->fetchUserCalendarByDatePeriod($this->translator, $period, $id, $request->get('session'));

		//Render the view
		return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar]+$context+$this->context);
	}
}
