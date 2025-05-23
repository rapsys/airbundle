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

class DanceController extends AbstractController {
	public function index(Request $request): Response {
		throw new \RuntimeException('TODO', 503);
		header('Content-Type: text/plain');
		var_dump('TODO');
		#var_dump($name);
		#var_dump($type);
		#var_dump($slug);
		exit;
	}

	/**
	 * Display dance by name
	 *
	 * @todo XXX: TODO: add <link rel="prev|next" for dances ? />
	 * @todo XXX: TODO: like described in: https://www.alsacreations.com/article/lire/1400-attribut-rel-relations.html#xnf-rel-attribute
	 * @todo XXX: TODO: or here: http://microformats.org/wiki/existing-rel-values#HTML5_link_type_extensions
	 *
	 * @TODO: faire plutôt comme /ville/x/y/paris
	 *
	 * @param Request $request The request instance
	 * @param string $name The shorted dance name
	 * @param string $dance The translated dance name
	 * @return Response The rendered view
	 */
	public function name(Request $request, $name, $dance): Response {
		throw new \RuntimeException('TODO', 503);

		//Get name
		$name = $this->slugger->unshort($sname = $name);

		//With existing dance
		if (empty($this->context['dances'] = $this->doctrine->getRepository(Dance::class)->findByName($name))) {
			//Throw not found
			//XXX: prevent slugger reverse engineering by not displaying decoded name
			throw $this->createNotFoundException($this->translator->trans('Unable to find dance %name%', ['%name%' => $sname]));
		}

		header('Content-Type: text/plain');
		var_dump('TODO');
		#var_dump($name);
		#var_dump($type);
		#var_dump($slug);
		exit;

		//Get city
		if (!($this->context['city'] = $this->doctrine->getRepository(Location::class)->findCityByLatitudeLongitudeAsArray(floatval($latitude), floatval($longitude)))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find city: %latitude%,%longitude%', ['%latitude%' => $latitude, '%longitude%' => $longitude]));
		}

		//Add calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period, !$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED'), floatval($latitude), floatval($longitude));

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
		if ($this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
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
		#$this->context['osm'] = $this->osm->getMultiImage($this->context['city']['link'], $this->context['city']['osm'], $this->modified->getTimestamp(), $latitude, $longitude, $this->context['locations'], $this->osm->getMultiZoom($latitude, $longitude, $this->context['locations'], 16));
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
		return $this->render('@RapsysAir/dance/name.html.twig', $this->context, $response);
	}

	/**
	 * List all sessions for the dance
	 *
	 * Display all sessions for the dance with an application or login form
	 *
	 * @TODO: add dance edit form ???
	 *
	 * @param Request $request The request instance
	 * @param int $id The dance id
	 * @param ?string $name The dance name
	 * @param ?string $type The dance type
	 *
	 * @return Response The rendered view
	 */
	public function view(Request $request, int $id, string $name, string $type): Response {
		//Without dance
		if (empty($this->context['dance'] = $this->doctrine->getRepository(Dance::class)->findOneByIdAsArray($id))) {
			//Throw 404
			throw $this->createNotFoundException($this->translator->trans('Unable to find dance: %id%', ['%id%' => $id]));
		}

		//With invalid name slug
		if ($name !== $this->context['dance']['slug']['name']) {
			//Redirect on correctly spelled location
			return $this->redirect($this->context['dance']['link'], Response::HTTP_MOVED_PERMANENTLY);
		}

		//With invalid type slug
		if ($type !== $this->context['dance']['slug']['type']) {
			//Redirect on correctly spelled location
			return $this->redirect($this->context['dance']['link'], Response::HTTP_MOVED_PERMANENTLY);
		}

		throw new \RuntimeException('TODO', 503);
		header('Content-Type: text/plain');
		var_dump('TODO');
		#var_dump($name);
		#var_dump($type);
		#var_dump($slug);
		exit;

		//Fetch calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period, !$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED'), $this->context['location']['latitude'], $this->context['location']['longitude']);

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
		$this->modified = max(array_merge([$this->context['location']['modified']], array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['locations']))));

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
		$this->context['multimap'] = $this->map->getMultiMap($this->context['location']['multimap'], $this->modified->getTimestamp(), $this->context['locations']);

		//Set keywords
		$this->context['keywords'] = [
			$this->context['location']['title'],
			$this->context['location']['city']['title'],
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
			$this->context['title']['page'] = $this->translator->trans('%dances% %location%', ['%dances%' => $dances, '%location%' => $this->context['location']['atin']]);

			//Set description
			$this->context['description'] = $this->translator->trans('%dances% indoor and outdoor calendar %location%', ['%dances%' => $dances, '%location%' => $this->context['location']['at']]);
		//Without dances
		} else {
			//Set title
			$this->context['title']['page'] = $this->translator->trans('Dance %location%', ['%location%' => $this->context['location']['atin']]);

			//Set description
			$this->context['description'] = $this->translator->trans('Indoor and outdoor dance calendar %location%', ['%location%' => $this->context['location']['at']]);
		}

		//Set locations description
		$this->context['locations_description'] = $this->translator->trans('Libre Air location list %location% %city%', ['%location%' => $this->context['location']['around'], '%city%' => $this->context['location']['city']['in']]);

		//Set locations link
		$this->context['locations_link'] = $this->context['location']['city']['link'];

		//Set locations title
		$this->context['locations_title'] = $this->context['location']['city']['title'].' ('.$this->context['location']['city']['id'].')';

		//Set alternates
		$this->context['alternates'] += $this->context['location']['alternates'];

		//Render the view
		return $this->render('@RapsysAir/location/view.html.twig', $this->context, $response);
	}
}
