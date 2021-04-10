<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Snippet;

class UserController extends DefaultController {
	/**
	 * List all users
	 *
	 * @desc Display all user with a group listed as users
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Libre Air users');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air user list');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('users'),
			$this->translator->trans('user list'),
			$this->translator->trans('listing'),
			$this->translator->trans('Libre Air')
		];

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Fetch users
		$users = $doctrine->getRepository(User::class)->findUserGroupedByTranslatedGroup($this->translator);

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

		//Without admin role
		if (!$this->isGranted('ROLE_ADMIN')) {
			//Remove users
			unset($users[$this->translator->trans('User')]);
		}

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/user/index.html.twig', ['title' => $title, 'section' => $section, 'users' => $users, 'locations' => $locations]+$this->context);
	}

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
	public function view(Request $request, $id): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch user
		if (empty($user = $doctrine->getRepository(User::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find user: %id%', ['%id%' => $id]));
		}

		//Prevent non admin access to non guest users
		if (!$this->isGranted('ROLE_ADMIN')) {
			//Get user token
			$token = new UsernamePasswordToken($user, null, 'none', $user->getRoles());

			//Check if guest access
			if (!($isGuest = $this->get('rapsys_user.access_decision_manager')->decide($token, ['ROLE_GUEST']))) {
				throw $this->createAccessDeniedException($this->translator->trans('Unable to access user: %id%', ['%id%' => $id]));
			}
		}

		//Set section
		$section = $user->getPseudonym();

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Set description
		$this->context['description'] = $this->translator->trans('%pseudonym% outdoor Argentine Tango session calendar', [ '%pseudonym%' => $user->getPseudonym() ]);

		//Set keywords
		$this->context['keywords'] = [
			$user->getPseudonym(),
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('calendar')
		];

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
			$this->context['forms']['snippets'] = [];

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
				#$form = $this->createForm('Rapsys\AirBundle\Form\SnippetType', $snippet, [
				$form = $this->container->get('form.factory')->createNamed('snipped_'.$request->getLocale().'_'.$locationId, 'Rapsys\AirBundle\Form\SnippetType', $snippet, [
					//Set the action
					//TODO: voir si on peut pas faire sauter ça ici
					'action' =>
						!empty($snippet->getId()) ?
						$this->generateUrl('rapsys_air_snippet_edit', ['id' => $snippet->getId()]) :
						$this->generateUrl('rapsys_air_snippet_add', ['location' => $locationId]),
					#'action' => $this->generateUrl('rapsys_air_snippet_add'),
					//Set the form attribute
					'attr' => [],
					//Set csrf_token_id
					//TODO: would maybe need a signature field
					//'csrf_token_id' => $request->getLocale().'_'.$id.'_'.$locationId
				]);
				#return $this->container->get('form.factory')->create($type, $data, $options);
				#public function createNamed($name, $type = 'Symfony\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = []);

				//Add form to context
				$this->context['forms']['snippets'][$locationId] = $form->createView();
			}
		}

		//Render the view
		return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
	}
}
