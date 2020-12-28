<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Snippet;

class OrganizerController extends DefaultController {
	/**
	 * List all organizers
	 *
	 * @desc Display all user with a group listed as organizers
	 *
	 * @param Request $request The request instance
	 * @param int $id The user id
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request) {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Argentine Tango organizers');

		//Set description
		$this->context['description'] = $this->translator->trans('Outdoor Argentine Tango organizer list');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('outdoor'),
			$this->translator->trans('organizer'),
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

		//Fetch organizers
		$organizers = $doctrine->getRepository(User::class)->findOrganizerGroupedByGroup($this->translator);

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/organizer/index.html.twig', ['title' => $title, 'section' => $section, 'organizers' => $organizers, 'locations' => $locations]+$context+$this->context);
	}

	/**
	 * List all sessions for the organizer
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
		if (empty($user = $doctrine->getRepository(User::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find organizer: %id%', ['%id%' => $id]));
		}

		//Set section
		$section = $user->getPseudonym();

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
			new \DateTime('Monday this week + 5 week')
		);

		//Fetch calendar
		//TODO: highlight with current session route parameter
		$calendar = $doctrine->getRepository(Session::class)->fetchUserCalendarByDatePeriod($this->translator, $period, $id, $request->get('session'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period, $id);

		//Create snippet forms for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Fetch all user snippet
			$snippets = $doctrine->getRepository(Snippet::class)->findByLocaleUserId($request->getLocale(), $id);

			//Rekey by location id
			$snippets = array_reduce($snippets, function($carry, $item){$carry[$item->getLocation()->getId()] = $item; return $carry;}, []);

			//Init snippets to context
			$context['snippets'] = [];

			//Iterate on locations
			foreach($locations as $locationId => $location) {
				//Init snippet
				$snippet = new Snippet();

				//Set default locale
				$snippet->setLocale($request->getLocale());

				//Set default user
				$snippet->setUser($user);

				//Set default location
				$snippet->setLocation($doctrine->getRepository(Location::class)->findOneById($locationId));

				//Get snippet
				if (!empty($snippets[$locationId])) {
					$snippet = $snippets[$locationId];
				}

				//Create SnippetType form
				$form = $this->createForm('Rapsys\AirBundle\Form\SnippetType', $snippet, [
					//Set the action
					//TODO: voir si on peut pas faire sauter ça ici
					'action' => !empty($snippet->getId())?$this->generateUrl('rapsys_air_snippet_edit', ['id' => $snippet->getId()]):$this->generateUrl('rapsys_air_snippet_add'),
					#'action' => $this->generateUrl('rapsys_air_snippet_add'),
					//Set the form attribute
					'attr' => [],
					//Set csrf_token_id
					//TODO: would maybe need a signature field
					//'csrf_token_id' => $request->getLocale().'_'.$id.'_'.$locationId
				]);

				//Add form to context
				$context['snippets'][$locationId] = $form->createView();
			}
		}

		//Render the view
		return $this->render('@RapsysAir/organizer/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$context+$this->context);
	}
}
