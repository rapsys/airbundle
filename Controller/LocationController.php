<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class LocationController extends DefaultController {
	/**
	 * Add location
	 *
	 * @desc Persist location in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least admin role
	 */
	public function add(Request $request) {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_ADMIN', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));

		//Create LocationType form
		$form = $this->createForm('Rapsys\AirBundle\Form\LocationType', null, [
			//Set the action
			'action' => $this->generateUrl('rapsys_air_location_add'),
			//Set the form attribute
			'attr' => []
		]);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Location add');

			//Set title
			$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

			//Render the view
			return $this->render('@RapsysAir/location/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get manager
		$manager = $doctrine->getManager();

		//Get location
		$location = $form->getData();

		//Set created
		$location->setCreated(new \DateTime('now'));

		//Set updated
		$location->setUpdated(new \DateTime('now'));

		//Queue location save
		$manager->persist($location);

		//Flush to get the ids
		$manager->flush();

		//Add notice
		$this->addFlash('notice', $this->translator->trans('Location %id% created', ['%id%' => $location->getId()]));

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

				//Check if location view route
				if ($name == 'rapsys_air_location_view' && !empty($route['id'])) {
					//Replace id
					$route['id'] = $location->getId();
				//Other routes
				} else {
					//Set location
					$route['location'] = $location->getId();
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
		return $this->redirectToRoute('rapsys_air', ['location' => $location->getId()]);
	}

	/**
	 * Edit location
	 *
	 * @desc Persist location in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function edit(Request $request, $id) {
		//Prevent non-admin to access here
		$this->denyAccessUnlessGranted('ROLE_ADMIN', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get location
		if (empty($location = $doctrine->getRepository(Location::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find location: %id%', ['%id%' => $id]));
		}

		//Create LocationType form
		$form = $this->createForm('Rapsys\AirBundle\Form\LocationType', $location, [
			//Set the action
			'action' => $this->generateUrl('rapsys_air_location_edit', ['id' => $id]),
			//Set the form attribute
			'attr' => []
		]);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Location %id%', ['%id%' => $id]);

			//Set title
			$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

			//Render the view
			return $this->render('@RapsysAir/location/edit.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
		}

		//Get manager
		$manager = $doctrine->getManager();

		//Set updated
		$location->setUpdated(new \DateTime('now'));

		//Queue location save
		$manager->persist($location);

		//Flush to get the ids
		$manager->flush();

		//Add notice
		$this->addFlash('notice', $this->translator->trans('Location %id% updated', ['%id%' => $id]));

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

				//Check if location view route
				if ($name == 'rapsys_air_location_view' && !empty($route['id'])) {
					//Replace id
					$route['id'] = $location->getId();
				//Other routes
				} else {
					//Set location
					$route['location'] = $location->getId();
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
		return $this->redirectToRoute('rapsys_air', ['location' => $location->getId()]);
	}

	/**
	 * List all locations
	 *
	 * @desc Display all locations
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Libre Air locations');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air location list');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('locations'),
			$this->translator->trans('location list'),
			$this->translator->trans('listing'),
			$this->translator->trans('Libre Air')
		];

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

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

		//Create location forms for role_admin
		if ($this->isGranted('ROLE_ADMIN')) {
			//Fetch all locations
			$locations = $doctrine->getRepository(Location::class)->findAll();

			//Rekey by id
			$locations = array_reduce($locations, function($carry, $item){$carry[$item->getId()] = $item; return $carry;}, []);

			//Init locations to context
			$this->context['forms']['locations'] = [];

			//Iterate on locations
			foreach($locations as $locationId => $location) {
				//Create LocationType form
				$form = $this->createForm('Rapsys\AirBundle\Form\LocationType', $location, [
					//Set the action
					'action' => $this->generateUrl('rapsys_air_location_edit', ['id' => $location->getId()]),
					//Set the form attribute
					'attr' => [],
					//Set block prefix
					//TODO: make this shit works to prevent label collision
					//XXX: see https://stackoverflow.com/questions/8703016/adding-a-prefix-to-a-form-label-for-translation
					'label_prefix' => 'location_'.$locationId
				]);

				//Add form to context
				$this->context['forms']['locations'][$locationId] = $form->createView();
			}

			//Create LocationType form
			$form = $this->createForm('Rapsys\AirBundle\Form\LocationType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_location_add'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$this->context['forms']['location'] = $form->createView();
		}

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/location/index.html.twig', ['title' => $title, 'section' => $section, 'locations' => $locations]+$this->context);
	}

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
	public function view(Request $request, $id): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch location
		if (empty($location = $doctrine->getRepository(Location::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find location: %id%', ['%id%' => $id]));
		}

		//Set section
		$section = $this->translator->trans('Argentine Tango at '.$location);

		//Set description
		$this->context['description'] = $this->translator->trans('Outdoor Argentine Tango session calendar %location%', [ '%location%' => $this->translator->trans('at '.$location) ]);

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans($location),
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('calendar')
		];

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

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
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, $id, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'), $request->getLocale());

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/location/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
	}
}
