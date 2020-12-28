<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class LocationController extends DefaultController {
	/**
	 * List all sessions for the location
	 *
	 * @desc Display all sessions for the location with an application or login form
	 *
	 * @param Request $request The request instance
	 * @param int $id The location id
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, $id) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch location
		if (empty($location = $doctrine->getRepository(Location::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find location: %id%', ['%id%' => $id]));
		}

		//Set section
		$section = $this->translator->trans('Argentine Tango at '.$location);

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
				'slot' => $doctrine->getRepository(Slot::class)->findOneById(3),
				//Set default location to current one
				'location' => $location
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
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, $id, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/location/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$context+$this->context);
	}
}
