<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Rapsys\AirBundle\Entity\Civility;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\AirBundle\Entity\User;

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

		//With admin role
		if ($this->isGranted('ROLE_ADMIN')) {
			//Set section
			$section = $this->translator->trans('Libre Air users');

			//Set description
			$this->context['description'] = $this->translator->trans('Libre Air user list');
		//Without admin role
		} else {
			//Set section
			$section = $this->translator->trans('Libre Air organizers');

			//Set description
			$this->context['description'] = $this->translator->trans('Libre Air organizers list');
		}

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

		//With admin role
		if ($this->isGranted('ROLE_ADMIN')) {
			//Display all users
			$this->context['groups'] = $users;
		//Without admin role
		} else {
			//Only display senior organizers
			$this->context['users'] = $users[$this->translator->trans('Senior')];
		}

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/user/index.html.twig', ['title' => $title, 'section' => $section, 'locations' => $locations]+$this->context);
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

		//Get user token
		$token = new UsernamePasswordToken($user, null, 'none', $user->getRoles());

		//Check if guest
		$isGuest = $this->get('rapsys_user.access_decision_manager')->decide($token, ['ROLE_GUEST']);

		//Prevent access when not admin, user is not guest and not currently logged user
		if (!$this->isGranted('ROLE_ADMIN') && empty($isGuest) && $user != $this->getUser()) {
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access user: %id%', ['%id%' => $id]));
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
		$calendar = $doctrine->getRepository(Session::class)->fetchUserCalendarByDatePeriod($this->translator, $period, $isGuest?$id:null, $request->get('session'));

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period, $id);

		//Create user form for admin or current user
		if ($this->isGranted('ROLE_ADMIN') || $user == $this->getUser()) {
			//Create SnippetType form
			$userForm = $this->createForm('Rapsys\AirBundle\Form\RegisterType', $user, [
				//Set action
				'action' => $this->generateUrl('rapsys_air_user_view', ['id' => $id]),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set civility class
				'civility_class' => Civility::class,
				//Disable mail
				'mail' => $this->isGranted('ROLE_ADMIN'),
				//Disable password
				'password' => false
			]);

			//Init user to context
			$this->context['forms']['user'] = $userForm->createView();

			//Check if submitted
			if ($request->isMethod('POST')) {
				//Refill the fields in case the form is not valid.
				$userForm->handleRequest($request);

				//Handle invalid form
				if (!$userForm->isSubmitted() || !$userForm->isValid()) {
					//Render the view
					return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
				}

				//Get data
				$data = $userForm->getData();

				//Get manager
				$manager = $doctrine->getManager();

				//Queue snippet save
				$manager->persist($data);

				//Flush to get the ids
				$manager->flush();

				//Add notice
				$this->addFlash('notice', $this->translator->trans('User %id% updated', ['%id%' => $id]));

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

						//Check if user view route
						if ($name == 'rapsys_air_user_view' && !empty($route['id'])) {
							//Replace id
							$route['id'] = $data->getId();
						//Other routes
						} else {
							//Set user
							$route['user'] = $data->getId();
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
				return $this->redirectToRoute('rapsys_air', ['user' => $data->getId()]);
			}
		}

		//Create snippet forms for role_guest
		if ($this->isGranted('ROLE_ADMIN') || ($this->isGranted('ROLE_GUEST') && $user == $this->getUser())) {
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
				$form = $this->container->get('form.factory')->createNamed('snipped_'.$request->getLocale().'_'.$locationId, 'Rapsys\AirBundle\Form\SnippetType', $snippet, [
					//Set the action
					'action' =>
						!empty($snippet->getId()) ?
						$this->generateUrl('rapsys_air_snippet_edit', ['id' => $snippet->getId()]) :
						$this->generateUrl('rapsys_air_snippet_add', ['location' => $locationId]),
					//Set the form attribute
					'attr' => []
				]);

				//Add form to context
				$this->context['forms']['snippets'][$locationId] = $form->createView();
			}
		}

		//Render the view
		return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
	}
}
