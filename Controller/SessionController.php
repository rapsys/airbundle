<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Location;

class SessionController extends AbstractController {
	/**
	 * List all sessions
	 *
	 * @desc Display all sessions with an application or login form
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Get locations
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllAsArray($this->period);

		//Add cities
		$this->context['cities'] = $this->doctrine->getRepository(Location::class)->findCitiesAsArray($this->period);

		//Add calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period, !$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED'), null, null, 1);

		//Add dances
		$this->context['dances'] = $this->doctrine->getRepository(Dance::class)->findNamesAsArray();

		//Set modified
		$this->modified = max(array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['cities'], $this->context['dances'])));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['calendar'], $this->context['cities'], $this->context['dances']))));

			//Set last modified
			$response->setLastModified($this->modified);

			//Set as public
			$response->setPublic();

			//Without role and modification
			if ($response->isNotModified($request)) {
				//Return 304 response
				return $response;
			}
		}

		//With cities
		if (!empty($this->context['cities'])) {
			//Set locations
			$locations = [];

			//Iterate on each cities
			foreach($this->context['cities'] as $city) {
				//Iterate on each locations
				foreach($city['locations'] as $location) {
					//Add location
					$locations[$location['id']] = $location;
				}
			}

			//Add multi
			$this->context['multimap'] = $this->map->getMultiMap($this->translator->trans('Libre Air cities sector map'), $this->modified->getTimestamp(), $locations);

			//Set cities
			$cities = array_map(function ($v) { return $v['in']; }, $this->context['cities']);

			//Set dances
			$dances = array_map(function ($v) { return $v['name']; }, $this->context['dances']);
		} else {
			//Set cities
			$cities = [];

			//Set dances
			$dances = [];
		}

		//Set keywords
		//TODO: use splice instead of that shit !!!
		//TODO: handle smartly indoor and outdoor !!!
		$this->context['keywords'] = array_values(
			array_merge(
				$dances,
				$cities,
				[
					$this->translator->trans('indoor'),
					$this->translator->trans('outdoor'),
					$this->translator->trans('sessions'),
					$this->translator->trans('session list'),
					$this->translator->trans('listing'),
					$this->translator->trans('Libre Air')
				]
			)
		);

		//Get textual cities
		$cities = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($cities, 0, -1))], array_slice($cities, -1)), 'strlen'));

		//Get textual dances
		$dances = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($dances, 0, -1))], array_slice($dances, -1)), 'strlen'));

		//Set title
		$this->context['title'] = $this->translator->trans('%dances% %cities% sessions', ['%dances%' => $dances, '%cities%' => $cities]);

		//Set description
		$this->context['description'] = $this->translator->trans('%dances% indoor and outdoor session calendar %cities%', ['%dances%' => $dances, '%cities%' => $cities]);

		//Render the view
		return $this->render('@RapsysAir/session/index.html.twig', $this->context);
	}

	/**
	 * List all sessions for tango argentin
	 *
	 * @desc Display all sessions in tango argentin json format
	 *
	 * @todo Drop it if unused by tangoargentin ???
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function tangoargentin(Request $request): Response {
		//Retrieve events to update
		$sessions = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period);

		//Init return array
		$ret = [];

		//Flatten sessions tree
		$sessions = array_reduce($sessions, function ($c, $v) { return array_merge($c, $v['sessions']); }, []);

		//Iterate on sessions
		foreach($sessions as $sessionId => $session) {
			//Set route params
			$routeParams = $this->router->match($session['link']);

			//Set route
			$route = $routeParams['_route'];

			//Drop _route from route params
			unset($routeParams['_route']);

			//Add session
			$ret[$session['id']] = [
				'start' => $session['start']->format(\DateTime::ISO8601),
				'stop' => $session['start']->format(\DateTime::ISO8601),
				'fromto' => $this->translator->trans('from %start% to %stop%', ['%start%' => $session['start']->format('H\hi'), '%stop%' => $session['stop']->format('H\hi')]),
				'title' => $this->slugger->latin($session['application']['user']['title'])/*.' '.$this->translator->trans('at '.$session['location']['title'])*/,
				'short' => $session['rate']['short'],
				'rate' => $session['rate']['title'],
				'location' => implode(' ', [$session['location']['address'], $session['location']['zipcode'], $session['location']['city']]),
				'status' => in_array('canceled', $session['class'])?'annulé':'confirmé',
				'modified' => $session['modified']->format(\DateTime::ISO8601),
				#'organizer' => $session['application']['user']['title'],
				#'source' => $this->router->generate('rapsys_air_session_view', ['id' => $sessionId, 'location' => $this->translator->trans($session['l_title'])], UrlGeneratorInterface::ABSOLUTE_URL)
				'source' => $this->router->generate($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL)
			];
		}

		//Set response
		$response = new Response(json_encode($ret));

		//Set header
		$response->headers->set('Content-Type', 'application/json');

		//Send response
		return $response;
	}

	/**
	 * Display session
	 *
	 * @todo XXX: TODO: add <link rel="prev|next" for sessions or classes ? />
	 * @todo XXX: TODO: like described in: https://www.alsacreations.com/article/lire/1400-attribut-rel-relations.html#xnf-rel-attribute
	 * @todo XXX: TODO: or here: http://microformats.org/wiki/existing-rel-values#HTML5_link_type_extensions
	 *
	 * @todo: generate a background from @RapsysAir/Resources/public/location/<location>.png or @RapsysAir/Resources/public/location/<user>/<location>.png when available
	 *
	 * @todo: generate a share picture @RapsysAir/seance/363/place-saint-sulpice/bal-et-cours-de-tango-argentin/milonga-raphael/share.jpeg ?
	 * (with date, organiser, type, location, times and logo ?)
	 *
	 * @todo: add picture stuff about location ???
	 *
	 * @param Request $request The request instance
	 * @param int $id The session id
	 *
	 * @return Response The rendered view
	 *
	 * @throws NotFoundHttpException When session is not found
	 */
	public function view(Request $request, int $id): Response {
		//Fetch session
		if (empty($this->context['session'] = $this->doctrine->getRepository(Session::class)->findOneByIdAsArray($id))) {
			//Session not found
			throw $this->createNotFoundException($this->translator->trans('Unable to find session: %id%', ['%id%' => $id]));
		}

		//Get locations at less than 1 km
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllByLatitudeLongitudeAsArray($this->context['session']['location']['latitude'], $this->context['session']['location']['longitude'], $this->period, 2);

		//Set modified
		//XXX: dance modified is already computed inside calendar modified
		$this->modified = max(array_merge([$this->context['session']['modified']], array_map(function ($v) { return $v['modified']; }, $this->context['locations'])));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['session'], $this->context['locations']))));

			//Set last modified
			$response->setLastModified($this->modified);

			//Set as public
			$response->setPublic();

			//Without role and modification
			if ($response->isNotModified($request)) {
				//Return 304 response
				return $response;
			}
		}

		//Get route
		$route = $request->attributes->get('_route');

		//Get route params
		$routeParams = $request->attributes->get('_route_params');

		//Disable redirect
		$redirect = false;

		//Without location or invalid location
		if (empty($routeParams['location']) || $this->context['session']['location']['slug'] !== $routeParams['location']) {
			//Set location
			$routeParams['location'] = $this->context['session']['location']['slug'];

			//Enable redirect
			$redirect = true;
		}

		//With dance slug without dance or invalid dance
		if (!empty($this->context['session']['application']['dance']['slug']) && (empty($routeParams['dance']) || $this->context['session']['application']['dance']['slug'] !== $routeParams['dance'])) {
			//Set dance
			$routeParams['dance'] = $this->context['session']['application']['dance']['slug'];

			//Enable redirect
			$redirect = true;
		//Without dance slug with dance
		} elseif (empty($this->context['session']['application']['dance']['slug']) && !empty($routeParams['dance'])) {
			//Set dance
			unset($routeParams['dance']);

			//Enable redirect
			$redirect = true;
		}

		//With user slug without user or invalid user
		if (!empty($this->context['session']['application']['user']['slug']) && (empty($routeParams['user']) || $this->context['session']['application']['user']['slug'] !== $routeParams['user'])) {
			//Set user
			$routeParams['user'] = $this->context['session']['application']['user']['slug'];

			//Enable redirect
			$redirect = true;
		//Without user slug with user
		} elseif (empty($this->context['session']['application']['user']['slug']) && !empty($routeParams['user'])) {
			//Set user
			unset($routeParams['user']);

			//Enable redirect
			$redirect = true;
		}

		//With redirect
		if ($redirect) {
			//Redirect to route
			return $this->redirectToRoute($route, $routeParams, $this->context['session']['stop'] <= new \DateTime('now') ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
		}

		//Add map
		$this->context['map'] = $this->map->getMap($this->context['session']['location']['map'], $this->modified->getTimestamp(), $this->context['session']['location']['latitude'], $this->context['session']['location']['longitude']);

		//Add multi map
		$this->context['multimap'] = $this->map->getMultiMap($this->context['session']['location']['multimap'], $this->modified->getTimestamp(), $this->context['locations']);

		//Set canonical
		$this->context['canonical'] = $this->context['session']['canonical'];

		//Set alternates
		$this->context['alternates'] = $this->context['session']['alternates'];

		//Set localization date formater
		$intlDate = new \IntlDateFormatter($this->locale, \IntlDateFormatter::TRADITIONAL, \IntlDateFormatter::NONE);

		//Set localization time formater
		$intlTime = new \IntlDateFormatter($this->locale, \IntlDateFormatter::NONE, \IntlDateFormatter::SHORT);

		//With application
		if (!empty($this->context['session']['application'])) {
			//Set title
			$this->context['title'] = $this->translator->trans('%dance% %id% by %pseudonym%', ['%id%' => $id, '%dance%' => $this->context['session']['application']['dance']['title'], '%pseudonym%' => $this->context['session']['application']['user']['title']]);

			//Set description
			$this->context['description'] = ucfirst($this->translator->trans('%dance% %location% %city% %slot% on %date% at %time%', [
				'%dance%' => $this->context['session']['application']['dance']['title'],
				'%location%' => $this->context['session']['location']['at'],
				'%city%' => $this->context['session']['location']['in'],
				'%slot%' => $this->context['session']['slot']['the'],
				'%date%' => $intlDate->format($this->context['session']['start']),
				'%time%' => $intlTime->format($this->context['session']['start']),
			]));

			//Set keywords
			//TODO: readd outdoor ???
			$this->context['keywords'] = [
				$this->context['session']['application']['dance']['type'],
				$this->context['session']['application']['dance']['name'],
				$this->context['session']['location']['title'],
				$this->context['session']['application']['user']['title'],
				$this->translator->trans($this->context['session']['location']['indoor']?'indoor':'outdoor')
			];
		//Without application
		} else {
			//Set title
			$this->context['title'] = $this->translator->trans('Session %id%', ['%id%' => $id]);

			//Set description
			$this->context['description'] = ucfirst($this->translator->trans('%location% %city% %slot% on %date% at %time%', [
				'%city%' => ucfirst($this->context['session']['location']['in']),
				'%location%' => $this->context['session']['location']['at'],
				'%slot%' => $this->context['session']['slot']['the'],
				'%date%' => $intlDate->format($this->context['session']['start']),
				'%time%' => $intlTime->format($this->context['session']['start'])
			]));

			//Add dance type
			//TODO: readd outdoor ???
			$this->context['keywords'] = [
				$this->context['session']['location']['title'],
				$this->translator->trans($this->context['session']['location']['indoor']?'indoor':'outdoor')
			];
		}

		//Set section
		$this->context['section'] = $this->context['session']['location']['title'];

		//Set facebook title
		$this->context['facebook']['metas']['og:title'] = $this->context['title'].' '.$this->context['session']['location']['at'];

		//Set facebook image
		$this->context['facebook'] = [
			'texts' => [
				$this->context['session']['application']['user']['title']??$this->context['title'] => [
					'font' => 'irishgrover',
					'size' => 110
				],
				ucfirst($intlDate->format($this->context['session']['start']))."\n".$this->translator->trans('Around %start% until %stop%', ['%start%' => $intlTime->format($this->context['session']['start']), '%stop%' => $intlTime->format($this->context['session']['stop'])]) => [
					'font' => 'irishgrover',
					'align' => 'left'
				],
				$this->context['session']['location']['at'] => [
					'align' => 'right',
					'font' => 'labelleaurore',
					'size' => 75
				]
			],
			'updated' => $this->context['session']['updated']->format('U')
		]+$this->context['facebook'];

		//Create application form for role_guest
		if ($this->checker->isGranted('ROLE_GUEST')) {
			//Set now
			$now = new \DateTime('now');

			//Default favorites and dances
			$danceFavorites = $dances = [];
			//Default dance
			$danceDefault = null;

			//With admin
			if ($this->checker->isGranted('ROLE_ADMIN')) {
				//Get favorites dances
				$danceFavorites = $this->doctrine->getRepository(Dance::class)->findByUserId($this->getUser()->getId());

				//Get dances
				$dances = $this->doctrine->getRepository(Dance::class)->findAllIndexed();

				//Set dance default
				$danceDefault = !empty($this->context['session']['application'])?$dances[$this->context['session']['application']['dance']['id']]:null;
			}

			//Create SessionType form
			//TODO: move to named form ???
			$sessionForm = $this->createForm('Rapsys\AirBundle\Form\SessionType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_air_session_view', ['id' => $id, 'location' => $this->context['session']['location']['slug'], 'dance' => $this->context['session']['application']['dance']['slug']??null, 'user' => $this->context['session']['application']['user']['slug']??null]),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set admin
				'admin' => $this->checker->isGranted('ROLE_ADMIN'),
				//Set dance choices
				'dance_choices' => $dances,
				//Set dance default
				'dance_default' => $danceDefault,
				//Set dance favorites
				'dance_favorites' => $danceFavorites,
				//Set to session slot or evening by default
				//XXX: default to Evening (3)
				'slot_default' => $this->doctrine->getRepository(Slot::class)->findOneById($this->context['session']['slot']['id']??3),
				//Set default user to current
				'user' => $this->getUser()->getId(),
				//Set date
				'date' => $this->context['session']['date'],
				//Set begin
				'begin' => $this->context['session']['begin'],
				//Set length
				'length' => $this->context['session']['length'],
				//Set raincancel
				'raincancel' => ($this->checker->isGranted('ROLE_ADMIN') || !empty($this->context['session']['application']['user']['id']) && $this->getUser()->getId() == $this->context['session']['application']['user']['id']) && $this->context['session']['rainfall'] >= 2,
				//Set cancel
				'cancel' => $this->checker->isGranted('ROLE_ADMIN') || in_array($this->getUser()->getId(), explode("\n", $this->context['session']['sau_id'])),
				//Set modify
				'modify' => $this->checker->isGranted('ROLE_ADMIN') || !empty($this->context['session']['application']['user']['id']) && $this->getUser()->getId() == $this->context['session']['application']['user']['id'] && $this->context['session']['stop'] >= $now && $this->checker->isGranted('ROLE_REGULAR'),
				//Set move
				'move' => $this->checker->isGranted('ROLE_ADMIN') || !empty($this->context['session']['application']['user']['id']) && $this->getUser()->getId() == $this->context['session']['application']['user']['id'] && $this->context['session']['stop'] >= $now && $this->checker->isGranted('ROLE_SENIOR'),
				//Set attribute
				'attribute' => $this->checker->isGranted('ROLE_ADMIN') && $this->context['session']['locked'] === null,
				//Set session
				'session' => $this->context['session']['id']
			]);

			//Refill the fields in case of invalid form
			$sessionForm->handleRequest($request);

			//With submitted form
			if ($sessionForm->isSubmitted() && $sessionForm->isValid()) {
				//Get data
				$data = $sessionForm->getData();

				//Fetch session
				$sessionObject = $this->doctrine->getRepository(Session::class)->findOneById($id);

				//Set user
				$userObject = $this->getUser();

				//Replace with requested user for admin
				if ($this->checker->isGranted('ROLE_ADMIN') && !empty($data['user'])) {
					$userObject = $this->doctrine->getRepository(User::class)->findOneById($data['user']);
				}

				//Set datetime
				$datetime = new \DateTime('now');

				//Set canceled time at start minus one day
				$canceled = (clone $sessionObject->getStart())->sub(new \DateInterval('P1D'));

				//Set action
				$action = [
					'raincancel' => $sessionForm->has('raincancel') && $sessionForm->get('raincancel')->isClicked(),
					'modify' => $sessionForm->has('modify') && $sessionForm->get('modify')->isClicked(),
					'move' => $sessionForm->has('move') && $sessionForm->get('move')->isClicked(),
					'cancel' => $sessionForm->has('cancel') && $sessionForm->get('cancel')->isClicked(),
					'forcecancel' => $sessionForm->has('forcecancel') && $sessionForm->get('forcecancel')->isClicked(),
					'attribute' => $sessionForm->has('attribute') && $sessionForm->get('attribute')->isClicked(),
					'autoattribute' => $sessionForm->has('autoattribute') && $sessionForm->get('autoattribute')->isClicked(),
					'lock' => $sessionForm->has('lock') && $sessionForm->get('lock')->isClicked(),
				];

				//With raincancel and application and (rainfall or admin)
				if ($action['raincancel'] && ($application = $sessionObject->getApplication()) && ($sessionObject->getRainfall() >= 2 || $this->checker->isGranted('ROLE_ADMIN'))) {
					//Cancel application at start minus one day
					$application->setCanceled($canceled);

					//Update time
					$application->setUpdated($datetime);

					//Insufficient rainfall
					//XXX: is admin
					if ($sessionObject->getRainfall() < 2) {
						//Set score
						//XXX: magic cheat score 42
						$application->setScore(42);
					}

					//Queue application save
					$this->manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));

					//Update time
					$sessionObject->setUpdated($datetime);

					//Queue session save
					$this->manager->persist($sessionObject);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//With modify
				} elseif ($action['modify']) {
					//With admin
					if ($this->checker->isGranted('ROLE_ADMIN')) {
						//Get application
						$application = $this->doctrine->getRepository(Application::class)->findOneBySessionUser($sessionObject, $userObject);

						//Set dance
						$application->setDance($data['dance']);

						//Queue session save
						$this->manager->persist($application);

						//Set slot
						$sessionObject->setSlot($data['slot']);

						//Set date
						$sessionObject->setDate($data['date']);
					}

					//Set begin
					$sessionObject->setBegin($data['begin']);

					//Set length
					$sessionObject->setLength($data['length']);

					//Update time
					$sessionObject->setUpdated($datetime);

					//Queue session save
					$this->manager->persist($sessionObject);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//With move
				} elseif ($action['move']) {
					//Set location
					$sessionObject->setLocation($this->doctrine->getRepository(Location::class)->findOneById($data['location']));

					//Update time
					$sessionObject->setUpdated($datetime);

					//Queue session save
					$this->manager->persist($sessionObject);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//With cancel or forcecancel
				} elseif ($action['cancel'] || $action['forcecancel']) {
					//Get application
					$application = $this->doctrine->getRepository(Application::class)->findOneBySessionUser($sessionObject, $userObject);

					//Not already canceled
					if ($application->getCanceled() === null) {
						//Cancel application
						$application->setCanceled($datetime);

						//Check if application is session application and (canceled 24h before start or forcecancel (as admin))
						#if ($sessionObject->getApplication() == $application && ($datetime < $canceled || $action['forcecancel'])) {
						if ($sessionObject->getApplication() == $application && $action['forcecancel']) {
							//Set score
							//XXX: magic cheat score 42
							$application->setScore(42);

							//Unattribute session
							$sessionObject->setApplication(null);

							//Update time
							$sessionObject->setUpdated($datetime);

							//Queue session save
							$this->manager->persist($sessionObject);

							//Add notice in flash message
							$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
						}
					//Already canceled
					} else {
						//Uncancel application
						$application->setCanceled(null);
					}

					//Update time
					$application->setUpdated($datetime);

					//Queue application save
					$this->manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
				//With attribute
				} elseif ($action['attribute']) {
					//Get application
					$application = $this->doctrine->getRepository(Application::class)->findOneBySessionUser($sessionObject, $userObject);

					//Already canceled
					if ($application->getCanceled() !== null) {
						//Uncancel application
						$application->setCanceled(null);
					}

					//Set score
					//XXX: magic cheat score 42
					$application->setScore(42);

					//Update time
					$application->setUpdated($datetime);

					//Queue application save
					$this->manager->persist($application);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));

					//Unattribute session
					$sessionObject->setApplication($application);

					//Update time
					$sessionObject->setUpdated($datetime);

					//Queue session save
					$this->manager->persist($sessionObject);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//With autoattribute
				} elseif ($action['autoattribute']) {
					//Get best application
					//XXX: best application may not issue result while grace time or bad behaviour
					if (!empty($application = $this->doctrine->getRepository(Session::class)->findBestApplicationById($id))) {
						//Attribute session
						$sessionObject->setApplication($application);

						//Update time
						$sessionObject->setUpdated($datetime);

						//Queue session save
						$this->manager->persist($sessionObject);

						//Add notice in flash message
						$this->addFlash('notice', $this->translator->trans('Session %id% auto attributed', ['%id%' => $id]));
					//No application
					} else {
						//Add warning in flash message
						$this->addFlash('warning', $this->translator->trans('Session %id% not auto attributed', ['%id%' => $id]));
					}
				//With lock
				} elseif ($action['lock']) {
					//Already locked
					if ($sessionObject->getLocked() !== null) {
						//Set uncanceled
						$canceled = null;

						//Unlock session
						$sessionObject->setLocked(null);
					//Not locked
					} else {
						//Get application
						if ($application = $sessionObject->getApplication()) {
							//Set score
							//XXX: magic cheat score 42
							$application->setScore(42);

							//Update time
							$application->setUpdated($datetime);

							//Queue application save
							$this->manager->persist($application);

							//Add notice in flash message
							$this->addFlash('notice', $this->translator->trans('Application %id% updated', ['%id%' => $application->getId()]));
						}

						//Unattribute session
						$sessionObject->setApplication(null);

						//Lock session
						$sessionObject->setLocked($datetime);
					}

					//Update time
					$sessionObject->setUpdated($datetime);

					//Queue session save
					$this->manager->persist($sessionObject);

					//Add notice in flash message
					$this->addFlash('notice', $this->translator->trans('Session %id% updated', ['%id%' => $id]));
				//Unknown action
				} else {
					//Add warning in flash message
					$this->addFlash('warning', $this->translator->trans('Session %id% not updated', ['%id%' => $id]));
				}

				//Flush to get the ids
				$this->manager->flush();

				//Redirect to cleanup the form
				return $this->redirectToRoute('rapsys_air_session_view', ['id' => $id, 'location' => $this->context['session']['location']['slug'], 'dance' => $this->context['session']['application']['dance']['slug']??null, 'user' => $this->context['session']['application']['user']['slug']??null]);
			}

			//Add form to context
			$this->context['forms']['session'] = $sessionForm->createView();
		}

		//Render the view
		return $this->render('@RapsysAir/session/view.html.twig', $this->context, $response);
	}
}
