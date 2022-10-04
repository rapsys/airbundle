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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;

/**
 * {@inheritdoc}
 */
class LocationController extends DefaultController {
	/**
	 * List all cities
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function cities(Request $request): Response {
		//Add cities
		$this->context['cities'] = $this->doctrine->getRepository(Location::class)->findCitiesAsArray($this->period);

		//Add dances
		$this->context['dances'] = $this->doctrine->getRepository(Dance::class)->findNamesAsArray();

		//Create response
		$response = new Response();

		//Set modified
		$this->modified = max(array_map(function ($v) { return $v['modified']; }, array_merge($this->context['cities'], $this->context['dances'])));

		//Add city multi
		foreach($this->context['cities'] as $id => $city) {
			//Add city multi
			#$this->osm->getMultiImage($city['link'], $city['osm'], $this->modified->getTimestamp(), $city['latitude'], $city['longitude'], $city['locations'], $this->osm->getMultiZoom($city['latitude'], $city['longitude'], $city['locations'], 16));
			$this->context['cities'][$id]['multimap'] = $this->map->getMultiMap($city['multimap'], $this->modified->getTimestamp(), $city['latitude'], $city['longitude'], $city['locations'], $this->map->getMultiZoom($city['latitude'], $city['longitude'], $city['locations']));
		}

		//With logged user
		if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['cities'], $this->context['dances']))));

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

		//Set section
		$this->context['title'] = $this->translator->trans('Libre Air cities');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air city list');

		//Set cities
		$cities = array_map(function ($v) { return $v['in']; }, $this->context['cities']);

		//Set dances
		$dances = array_map(function ($v) { return $v['name']; }, $this->context['dances']);

		//Set indoors
		$indoors = array_reduce($this->context['cities'], function ($c, $v) { return array_merge($c, $v['indoors']); }, []);

		//Set keywords
		$this->context['keywords'] = array_values(
			array_merge(
				[
					$this->translator->trans('Cities'),
					$this->translator->trans('City list'),
					$this->translator->trans('Listing'),
				],
				$cities,
				$indoors,
				[
					$this->translator->trans('calendar'),
					$this->translator->trans('Libre Air')
				]
			)
		);

		//Render the view
		return $this->render('@RapsysAir/location/cities.html.twig', $this->context, $response);
	}

	/**
	 * Display city
	 *
	 * @todo XXX: TODO: add <link rel="prev|next" for sessions or classes ? />
	 * @todo XXX: TODO: like described in: https://www.alsacreations.com/article/lire/1400-attribut-rel-relations.html#xnf-rel-attribute
	 * @todo XXX: TODO: or here: http://microformats.org/wiki/existing-rel-values#HTML5_link_type_extensions
	 *
	 * @param Request $request The request instance
	 * @param float $latitude The city latitude
	 * @param float $longitude The city longitude
	 * @return Response The rendered view
	 */
	public function city(Request $request, float $latitude, float $longitude, string $city): Response {
		//Get city
		if (!($this->context['city'] = $this->doctrine->getRepository(Location::class)->findCityByLatitudeLongitudeAsArray(floatval($latitude), floatval($longitude)))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find city: %latitude%,%longitude%', ['%latitude%' => $latitude, '%longitude%' => $longitude]));
		}

		//Add calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsArray($this->period, $request->getLocale(), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'), floatval($latitude), floatval($longitude));

		//Set dances
		$this->context['dances'] = [];

		//Iterate on each calendar
		foreach($this->context['calendar'] as $date => $calendar) {
			//Iterate on each session
			foreach($calendar['sessions'] as $sessionId => $session) {
				//Session with application dance
				if (!empty($session['application']['dance'])) {
					//Add dance
					$this->context['dances'][$session['application']['dance']['id']] = $session['application']['dance'];
				}
			}
		}

		//Add locations
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllByLatitudeLongitudeAsArray(floatval($latitude), floatval($longitude), $this->period);

		//Set modified
		//XXX: dance modified is already computed inside calendar modified
		$this->modified = max(array_merge([$this->context['city']['updated']], array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['locations']))));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['city'], $this->context['dances'], $this->context['calendar'], $this->context['locations']))));

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

		//Add multi
		$this->context['multimap'] = $this->map->getMultiMap($this->context['city']['multimap'], $this->modified->getTimestamp(), $latitude, $longitude, $this->context['locations'], $this->map->getMultiZoom($latitude, $longitude, $this->context['locations']));

		//Set keywords
		$this->context['keywords'] = [
			$this->context['city']['city'],
			$this->translator->trans('Indoor'),
			$this->translator->trans('Outdoor'),
			$this->translator->trans('Calendar'),
			$this->translator->trans('Libre Air')
		];

		//With context dances
		if (!empty($this->context['dances'])) {
			//Set dances
			$dances = array_map(function ($v) { return $v['name']; }, $this->context['dances']);

			//Insert dances in keywords
			array_splice($this->context['keywords'], 1, 0, $dances);

			//Get textual dances
			$dances = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($dances, 0, -1))], array_slice($dances, -1)), 'strlen'));

			//Set title
			$this->context['title'] = $this->translator->trans('%dances% %city%', ['%dances%' => $dances, '%city%' => $this->context['city']['in']]);

			//Set description
			$this->context['description'] = $this->translator->trans('%dances% indoor and outdoor calendar %city%', ['%dances%' => $dances, '%city%' => $this->context['city']['in']]);
		} else {
			//Set title
			$this->context['title'] = $this->translator->trans('Dance %city%', ['%city%' => $this->context['city']['in']]);

			//Set description
			$this->context['description'] = $this->translator->trans('Indoor and outdoor dance calendar %city%', ['%city%' => $this->context['city']['in']]);
		}

		//Set locations description
		$this->context['locations_description'] = $this->translator->trans('Libre Air location list %city%', ['%city%' => $this->context['city']['in']]);

		//Render the view
		return $this->render('@RapsysAir/location/city.html.twig', $this->context, $response);
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
		//Get locations
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllAsArray($this->period);

		//Set modified
		$this->modified = max(array_map(function ($v) { return $v['updated']; }, $this->context['locations']));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize($this->context['locations'])));

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

		//Set latitudes
		$latitudes = array_map(function ($v) { return $v['latitude']; }, $this->context['locations']);

		//Set latitude
		$latitude = round(array_sum($latitudes)/count($latitudes), 6);

		//Set longitudes
		$longitudes = array_map(function ($v) { return $v['longitude']; }, $this->context['locations']);

		//Set longitude
		$longitude = round(array_sum($longitudes)/count($longitudes), 6);

		//Add multi map
		$this->context['multimap'] = $this->map->getMultiMap($this->translator->trans('Libre Air locations sector map'), $this->modified->getTimestamp(), $latitude, $longitude, $this->context['locations'], $this->map->getMultiZoom($latitude, $longitude, $this->context['locations']));

		//Set title
		$this->context['title'] = $this->translator->trans('Libre Air locations');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air location list');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('locations'),
			$this->translator->trans('location list'),
			$this->translator->trans('listing'),
			$this->translator->trans('Libre Air')
		];

		//Create location forms for role_admin
		if ($this->isGranted('ROLE_ADMIN')) {
			//Fetch all locations
			$locations = $this->doctrine->getRepository(Location::class)->findAll();

			//Init locations to context
			$this->context['forms']['locations'] = [];

			//Iterate on locations
			foreach($this->context['locations'] as $id => $location) {
				//Create LocationType form
				$form = $this->factory->createNamed(
					//Set form id
					'locations_'.$id,
					//Set form type
					'Rapsys\AirBundle\Form\LocationType',
					//Set form data
					$locations[$location['id']],
					//Set the form attributes
					['attr' => []]
				);

				//Refill the fields in case of invalid form
				$form->handleRequest($request);

				//Handle valid form
				if ($form->isSubmitted() && $form->isValid()) {
					//Get data
					$data = $form->getData();

					//Set updated
					$data->setUpdated(new \DateTime('now'));

					//Queue location save
					$this->manager->persist($data);

					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Location %id% updated', ['%id%' => $location['id']]));

					//Redirect to cleanup the form
					return $this->redirectToRoute('rapsys_air_location', ['location' => $location['id']]);
				}

				//Add form to context
				$this->context['forms']['locations'][$id] = $form->createView();
			}

			//Create LocationType form
			$form = $this->factory->createNamed(
				//Set form id
				'locations',
				//Set form type
				'Rapsys\AirBundle\Form\LocationType',
				//Set form data
				new Location(),
				//Set the form attributes
				['attr' => ['class' => 'col']]
			);

			//Refill the fields in case of invalid form
			$form->handleRequest($request);

			//Handle valid form
			if ($form->isSubmitted() && $form->isValid()) {
				//Get data
				$data = $form->getData();

				//Queue location save
				$this->manager->persist($data);

				//Flush to get the ids
				$this->manager->flush();

				//Add notice
				$this->addFlash('notice', $this->translator->trans('Location created'));

				//Redirect to cleanup the form
				return $this->redirectToRoute('rapsys_air_location', ['location' => $data->getId()]);
			}

			//Add form to context
			$this->context['forms']['location'] = $form->createView();
		}

		//Render the view
		return $this->render('@RapsysAir/location/index.html.twig', $this->context);
	}

	/**
	 * List all sessions for the location
	 *
	 * Display all sessions for the location with an application or login form
	 *
	 * @TODO: add location edit form ???
	 *
	 * @param Request $request The request instance
	 * @param int $id The location id
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, int $id): Response {
		//Without location
		if (empty($this->context['location'] = $this->doctrine->getRepository(Location::class)->findOneByIdAsArray($id, $this->locale))) {
			//Throw 404
			throw $this->createNotFoundException($this->translator->trans('Unable to find location: %id%', ['%id%' => $id]));
		}

		//Fetch calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsArray($this->period, $this->locale, !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'), $this->context['location']['latitude'], $this->context['location']['longitude']);

		//Set dances
		$this->context['dances'] = [];

		//Iterate on each calendar
		foreach($this->context['calendar'] as $date => $calendar) {
			//Iterate on each session
			foreach($calendar['sessions'] as $sessionId => $session) {
				//Session with application dance
				if (!empty($session['application']['dance'])) {
					//Add dance
					$this->context['dances'][$session['application']['dance']['id']] = $session['application']['dance'];
				}
			}
		}

		//Get locations at less than 2 km
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllByLatitudeLongitudeAsArray($this->context['location']['latitude'], $this->context['location']['longitude'], $this->period, 2);

		//Set modified
		//XXX: dance modified is already computed inside calendar modified
		$this->modified = max(array_merge([$this->context['location']['updated']], array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['locations']))));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['location'], $this->context['calendar'], $this->context['locations']))));

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

		//Add multi map
		$this->context['multimap'] = $this->map->getMultiMap($this->context['location']['multimap'], $this->modified->getTimestamp(), $this->context['location']['latitude'], $this->context['location']['longitude'], $this->context['locations'], $this->map->getMultiZoom($this->context['location']['latitude'], $this->context['location']['longitude'], $this->context['locations']));

		//Set keywords
		$this->context['keywords'] = [
			$this->context['location']['title'],
			$this->context['location']['city'],
			$this->translator->trans($this->context['location']['indoor']?'Indoor':'Outdoor'),
			$this->translator->trans('Calendar'),
			$this->translator->trans('Libre Air')
		];

		//With dances
		if (!empty($this->context['dances'])) {
			//Set dances
			$dances = array_map(function ($v) { return $v['name']; }, $this->context['dances']);

			//Insert dances in keywords
			array_splice($this->context['keywords'], 2, 0, $dances);

			//Get textual dances
			$dances = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($dances, 0, -1))], array_slice($dances, -1)), 'strlen'));

			//Set title
			$this->context['title'] = $this->translator->trans('%dances% %location%', ['%dances%' => $dances, '%location%' => $this->context['location']['atin']]);

			//Set description
			$this->context['description'] = $this->translator->trans('%dances% indoor and outdoor calendar %location%', ['%dances%' => $dances, '%location%' => $this->context['location']['at']]);
		//Without dances
		} else {
			//Set title
			$this->context['title'] = $this->translator->trans('Dance %location%', ['%location%' => $this->context['location']['atin']]);

			//Set description
			$this->context['description'] = $this->translator->trans('Indoor and outdoor dance calendar %location%', [ '%location%' => $this->context['location']['at'] ]);
		}

		//Set locations description
		$this->context['locations_description'] = $this->translator->trans('Libre Air location list %location%', ['%location%' => $this->context['location']['atin']]);

		//Set alternates
		$this->context['alternates'] += $this->context['location']['alternates'];

		//Render the view
		return $this->render('@RapsysAir/location/view.html.twig', $this->context, $response);
	}
}
