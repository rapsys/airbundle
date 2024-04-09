<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Repository;

use Doctrine\ORM\Query\ResultSetMapping;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use Rapsys\AirBundle\Repository;

/**
 * LocationRepository
 *
 * @TODO: use new window function syntax https://mariadb.com/kb/en/window-functions-overview/ MAX(updated) OVER (PARTITION updated) AS modified ???
 */
class LocationRepository extends Repository {
	/**
	 * Find locations
	 *
	 * @return array
	 */
	public function findAll(): array {
		//Get all locations index by id
		return $this->createQueryBuilder('location', 'location.id')->getQuery()->getResult();
	}

	/**
	 * Find locations as array
	 *
	 * @param DatePeriod $period The period
	 * @return array The locations array
	 */
	public function findAllAsArray(\DatePeriod $period): array {
		//Set the request
		//TODO: ajouter pays ???
		$req = <<<SQL
SELECT
	l.id,
	l.title,
	l.latitude,
	l.longitude,
	l.indoor,
	l.updated
FROM Rapsys\AirBundle\Entity\Location AS l
LEFT JOIN Rapsys\AirBundle\Entity\Session AS s ON (l.id = s.location_id)
GROUP BY l.id
ORDER BY COUNT(IF(s.date BETWEEN :begin AND :end, s.id, NULL)) DESC, COUNT(s.id) DESC, l.id
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('title', 'title', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			->addScalarResult('indoor', 'indoor', 'boolean')
			->addScalarResult('count', 'count', 'integer')
			->addScalarResult('updated', 'updated', 'datetime');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each city
		foreach($result as $data) {
			//Add to return
			$return[] = [
				'id' => $data['id'],
				'title' => $title = $this->translator->trans($data['title']),
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'updated' => $data['updated'],
				//XXX: Useless ???
				'slug' => $location = $this->slugger->slug($title),
				'link' => $this->router->generate('rapsysair_location_view', ['id' => $data['id'], 'location' => $this->slugger->slug($location)])
			];
		}

		//Return return
		return $return;
	}

	/**
	 * Find cities as array
	 *
	 * @param DatePeriod $period The period
	 * @param int $count The session count
	 * @return array The cities array
	 */
	public function findCitiesAsArray(\DatePeriod $period, int $count = 1): array {
		//Set the request
		$req = <<<SQL
SELECT
	SUBSTRING(a.zipcode, 1, 2) AS id,
	a.city AS city,
	ROUND(AVG(a.latitude), 6) AS latitude,
	ROUND(AVG(a.longitude), 6) AS longitude,
	GROUP_CONCAT(a.id ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS ids,
	GROUP_CONCAT(a.title ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS titles,
	GROUP_CONCAT(a.latitude ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS latitudes,
	GROUP_CONCAT(a.longitude ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS longitudes,
	GROUP_CONCAT(a.indoor ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS indoors,
	GROUP_CONCAT(a.count ORDER BY a.pcount DESC, a.count DESC, a.id SEPARATOR "\\n") AS counts,
	MAX(a.modified) AS modified
FROM (
	SELECT
		l.id,
		l.city,
		l.title,
		l.latitude,
		l.longitude,
		l.indoor,
		GREATEST(l.created, l.updated, COALESCE(s.created, '1970-01-01'), COALESCE(s.updated, '1970-01-01')) AS modified,
		l.zipcode,
		COUNT(s.id) AS count,
		COUNT(IF(s.date BETWEEN :begin AND :end, s.id, NULL)) AS pcount
	FROM Rapsys\AirBundle\Entity\Location AS l
	LEFT JOIN Rapsys\AirBundle\Entity\Session AS s ON (l.id = s.location_id)
	GROUP BY l.id
	ORDER BY NULL
	LIMIT 0, :limit
) AS a
GROUP BY a.city, SUBSTRING(a.zipcode, 1, 2)
ORDER BY a.city, a.zipcode
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('city', 'city', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			//XXX: is a string because of \n separator
			->addScalarResult('ids', 'ids', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('titles', 'titles', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('latitudes', 'latitudes', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('longitudes', 'longitudes', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('indoors', 'indoors', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('counts', 'counts', 'string')
			->addScalarResult('modified', 'modified', 'datetime')
			->addIndexByScalar('city');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each city
		foreach($result as $city => $data) {
			//Set titles
			$titles = explode("\n", $data['titles']);

			//Set latitudes
			$latitudes = explode("\n", $data['latitudes']);

			//Set longitudes
			$longitudes = explode("\n", $data['longitudes']);

			//Set indoors
			$indoors = explode("\n", $data['indoors']);

			//Set counts
			$counts = explode("\n", $data['counts']);

			//With unsufficient count
			if ($count && $counts[0] < $count) {
				//Skip empty city
				//XXX: count are sorted so only check first
				continue;
			}

			//Set locations
			$data['locations'] = [];

			//Iterate on each location
			foreach(explode("\n", $data['ids']) as $k => $id) {
				//With unsufficient count
				if ($count && $counts[$k] < $count) {
					//Skip empty city
					//XXX: count are sorted so only check first
					continue;
				}

				//Add location
				$data['locations'][] = [
					'id' => $id,
					'title' => $location = $this->translator->trans($titles[$k]),
					'latitude' => floatval($latitudes[$k]),
					'longitude' => floatval($longitudes[$k]),
					'indoor' => $indoors[$k] == 0 ? $this->translator->trans('outdoor') : $this->translator->trans('indoor'),
					'link' => $this->router->generate('rapsysair_location_view', ['id' => $id, 'location' => $this->slugger->slug($location)])
				];
			}

			//Add to return
			$return[$city] = [
				'id' => $data['id'],
				'city' => $data['city'],
				'in' => $this->translator->trans('in '.$data['city']),
				'indoors' => array_map(function ($v) { return $v == 0 ? $this->translator->trans('outdoor') : $this->translator->trans('indoor'); }, array_unique($indoors)),
				'multimap' => $this->translator->trans($data['city'].' sector map'),
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'modified' => $data['modified'],
				//XXX: Useless ???
				'slug' => $city = $this->slugger->slug($data['city']),
				'link' => $this->router->generate('rapsysair_city_view', ['city' => $city, 'latitude' => $data['latitude'], 'longitude' => $data['longitude']]),
				'locations' => $data['locations']
			];
		}

		//Return return
		return $return;
	}

	/**
	 * Find city by latitude and longitude as array
	 *
	 * @param float $latitude The latitude
	 * @param float $longitude The longitude
	 * @return ?array The cities array
	 */
	public function findCityByLatitudeLongitudeAsArray(float $latitude, float $longitude): ?array {
		//Set the request
		$req = <<<SQL
SELECT
	SUBSTRING(l.zipcode, 1, 2) AS id,
	l.city AS city,
	ROUND(AVG(l.latitude), 6) AS latitude,
	ROUND(AVG(l.longitude), 6) AS longitude,
	MAX(l.updated) AS updated
FROM Rapsys\AirBundle\Entity\Location AS l
GROUP BY city, SUBSTRING(l.zipcode, 1, 2)
ORDER BY ACOS(SIN(RADIANS(:latitude))*SIN(RADIANS(l.latitude))+COS(RADIANS(:latitude))*COS(RADIANS(l.latitude))*COS(RADIANS(:longitude - l.longitude)))*40030.17/2/PI()
LIMIT 0, 1
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('city', 'city', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			->addScalarResult('updated', 'updated', 'datetime')
			->addIndexByScalar('city');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('latitude', $latitude)
			->setParameter('longitude', $longitude)
			->getOneOrNullResult();

		//Without result
		if ($result === null) {
			//Return result
			return $result;
		}

		//Return result
		return [
			'id' => $result['id'],
			'city' => $result['city'],
			'latitude' => $result['latitude'],
			'longitude' => $result['longitude'],
			'updated' => $result['updated'],
			'in' => $this->translator->trans('in '.$result['city']),
			'multimap' => $this->translator->trans($result['city'].' sector map'),
			//XXX: Useless ???
			'slug' => $slug = $this->slugger->slug($result['city']),
			'link' => $this->router->generate('rapsysair_city_view', ['city' => $slug, 'latitude' => $result['latitude'], 'longitude' => $result['longitude']])
		];
	}

	/**
	 * Find locations by latitude and longitude sorted by period as array
	 *
	 * @TODO: find all other locations when current one has no sessions ???
	 *
	 * @param float $latitude The latitude
	 * @param float $longitude The longitude
	 * @param DatePeriod $period The period
	 * @param int $count The session count
	 * @param float $distance The distance
	 * @return array The locations array
	 */
	public function findAllByLatitudeLongitudeAsArray(float $latitude, float $longitude, \DatePeriod $period, int $count = 1, float $distance = 15): array {
		//Set earth radius
		$radius = 40030.17/2/pi();

		//Compute min latitude
		$minlat = min(rad2deg(asin(sin(deg2rad($latitude))*cos($distance/$radius) + cos(deg2rad($latitude))*sin($distance/$radius)*cos(deg2rad(180)))), $latitude);

		//Compute max latitude
		$maxlat = max(rad2deg(asin(sin(deg2rad($latitude))*cos($distance/$radius) + cos(deg2rad($latitude))*sin($distance/$radius)*cos(deg2rad(0)))), $latitude);

		//Compute min longitude
		$minlong = fmod((rad2deg((deg2rad($longitude) + atan2(sin(deg2rad(-90))*sin($distance/$radius)*cos(deg2rad($minlat)), cos($distance/$radius) - sin(deg2rad($minlat)) * sin(deg2rad($minlat))))) + 180), 360) - 180;

		//Compute max longi
		$maxlong = fmod((rad2deg((deg2rad($longitude) + atan2(sin(deg2rad(90))*sin($distance/$radius)*cos(deg2rad($maxlat)), cos($distance/$radius) - sin(deg2rad($maxlat)) * sin(deg2rad($maxlat))))) + 180), 360) - 180;

		//Set the request
		//TODO: see old request before commit to sort session count, distance and then by id ?
		//TODO: see to sort by future session count, historical session count, distance and then by id ?
		//TODO: do the same for cities and city ?
		$req = <<<SQL
SELECT
	a.id,
	a.title,
	a.latitude,
	a.longitude,
	a.created,
	a.updated,
	MAX(GREATEST(a.modified, COALESCE(s.created, '1970-01-01'), COALESCE(s.updated, '1970-01-01'))) AS modified,
	COUNT(s.id) AS count
FROM (
	SELECT
		l.id,
		l.title,
		l.latitude,
		l.longitude,
		l.created,
		l.updated,
		GREATEST(l.created, l.updated) AS modified
	FROM Rapsys\AirBundle\Entity\Location AS l
	WHERE l.latitude BETWEEN :minlat AND :maxlat AND l.longitude BETWEEN :minlong AND :maxlong
	LIMIT 0, :limit
) AS a
LEFT JOIN Rapsys\AirBundle\Entity\Session s ON (s.location_id = a.id)
GROUP BY a.id
ORDER BY COUNT(IF(s.date BETWEEN :begin AND :end, s.id, NULL)) DESC, count DESC, a.id
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('title', 'title', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			->addScalarResult('created', 'created', 'datetime')
			->addScalarResult('updated', 'updated', 'datetime')
			->addScalarResult('modified', 'modified', 'datetime')
			->addScalarResult('count', 'count', 'integer');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->setParameter('minlat', $minlat)
			->setParameter('maxlat', $maxlat)
			->setParameter('minlong', $minlong)
			->setParameter('maxlong', $maxlong)
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each location
		foreach($result as $id => $data) {
			//With active locations
			if ($count && $data['count'] < $count) {
				//Skip unactive locations
				continue;
			}

			//Add location
			$return[$id] = [
				'id' => $data['id'],
				'title' => $title = $this->translator->trans($data['title']),
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'created' => $data['created'],
				'updated' => $data['updated'],
				'modified' => $data['modified'],
				'count' => $data['count'],
				'slug' => $slug = $this->slugger->slug($title),
				'link' => $this->router->generate('rapsysair_location_view', ['id' => $data['id'], 'location' => $slug])
			];
		}

		//Return return
		return $return;
	}

	/**
	 * Find locations by user id sorted by period as array
	 *
	 * @param int $userId The user id
	 * @param DatePeriod $period The period
	 * @return array The locations array
	 */
	public function findAllByUserIdAsArray(int $userId, \DatePeriod $period, $distance = 15): array {
		//Set the request
		//TODO: ajouter pays ???
		$req = <<<SQL
SELECT
	a.id,
	a.title,
	a.city,
	a.latitude,
	a.longitude,
	a.created,
	a.updated,
	MAX(GREATEST(a.modified, COALESCE(s3.created, '1970-01-01'), COALESCE(s3.updated, '1970-01-01'))) AS modified,
	a.pcount,
	COUNT(s3.id) AS tcount
FROM (
	SELECT
		b.id,
		b.title,
		b.city,
		b.latitude,
		b.longitude,
		b.created,
		b.updated,
		MAX(GREATEST(b.modified, COALESCE(s2.created, '1970-01-01'), COALESCE(s2.updated, '1970-01-01'))) AS modified,
		COUNT(s2.id) AS pcount
	FROM (
		SELECT
			l2.id,
			l2.city,
			l2.title,
			l2.latitude,
			l2.longitude,
			l2.created,
			l2.updated,
			GREATEST(l2.created, l2.updated) AS modified
		FROM (
			SELECT
				l.id,
				l.latitude,
				l.longitude
			FROM applications AS a
			JOIN sessions AS s ON (s.id = a.session_id)
			JOIN locations AS l ON (l.id = s.location_id)
			WHERE a.user_id = :id
			GROUP BY l.id
			ORDER BY NULL
			LIMIT 0, :limit
		) AS a
		JOIN locations AS l2
		WHERE ACOS(SIN(RADIANS(a.latitude))*SIN(RADIANS(l2.latitude))+COS(RADIANS(a.latitude))*COS(RADIANS(l2.latitude))*COS(RADIANS(a.longitude - l2.longitude)))*40030.17/2/PI() BETWEEN 0 AND :distance
		GROUP BY l2.id
		ORDER BY NULL
		LIMIT 0, :limit
	) AS b
	LEFT JOIN sessions AS s2 ON (s2.location_id = b.id AND s2.date BETWEEN :begin AND :end)
	GROUP BY b.id
	ORDER BY NULL
	LIMIT 0, :limit
) AS a
LEFT JOIN sessions AS s3 ON (s3.location_id = a.id)
GROUP BY a.id
ORDER BY pcount DESC, tcount DESC, a.id
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('title', 'title', 'string')
			->addScalarResult('city', 'city', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			->addScalarResult('created', 'created', 'datetime')
			->addScalarResult('updated', 'updated', 'datetime')
			->addScalarResult('modified', 'modified', 'datetime')
			->addScalarResult('pcount', 'pcount', 'integer')
			->addScalarResult('tcount', 'tcount', 'integer');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->setParameter('id', $userId)
			->setParameter('distance', $distance)
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each location
		foreach($result as $id => $data) {
			//With active locations
			if (!empty($result[0]['tcount']) && empty($data['tcount'])) {
				//Skip unactive locations
				break;
			}

			//Add location
			$return[$id] = [
				'id' => $data['id'],
				'city' => $data['city'],
				'title' => $title = $this->translator->trans($data['title']),
				'at' => $this->translator->trans('at '.$data['title']),
				'miniature' => $this->translator->trans($data['title'].' miniature'),
				'latitude' => $data['latitude'],
				'longitude' => $data['longitude'],
				'created' => $data['created'],
				'updated' => $data['updated'],
				'modified' => $data['modified'],
				'pcount' => $data['pcount'],
				'tcount' => $data['tcount'],
				'slug' => $slug = $this->slugger->slug($title),
				'link' => $this->router->generate('rapsysair_location_view', ['id' => $data['id'], 'location' => $slug])
			];
		}

		//Return return
		return $return;
	}

	/**
	 * Find location as array by id
	 *
	 * @param int $id The location id
	 * @param string $locale The locale
	 * @return array The location data
	 */
	public function findOneByIdAsArray(int $id, string $locale): ?array {
		//Set the request
		$req = <<<SQL
SELECT
	l.id,
	l.title,
	l.city,
	l.latitude,
	l.longitude,
	l.indoor,
	l.zipcode,
	MAX(GREATEST(l.created, l.updated, l2.created, l2.updated)) AS modified,
	SUBSTRING(l.zipcode, 1, 2) AS city_id,
	ROUND(AVG(l2.latitude), 6) AS city_latitude,
	ROUND(AVG(l2.longitude), 6) AS city_longitude
FROM Rapsys\AirBundle\Entity\Location AS l
JOIN Rapsys\AirBundle\Entity\Location AS l2 ON (l2.city = l.city AND SUBSTRING(l2.zipcode, 1, 2) = SUBSTRING(l.zipcode, 1, 2))
WHERE l.id = :id
GROUP BY l.id
LIMIT 0, 1
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('title', 'title', 'string')
			->addScalarResult('city', 'city', 'string')
			->addScalarResult('latitude', 'latitude', 'float')
			->addScalarResult('longitude', 'longitude', 'float')
			->addScalarResult('indoor', 'indoor', 'boolean')
			->addScalarResult('zipcode', 'zipcode', 'string')
			->addScalarResult('modified', 'modified', 'datetime')
			->addScalarResult('city_id', 'city_id', 'integer')
			->addScalarResult('city_latitude', 'city_latitude', 'float')
			->addScalarResult('city_longitude', 'city_longitude', 'float')
			->addIndexByScalar('id');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('id', $id)
			->getOneOrNullResult();

		//Without result
		if ($result === null) {
			//Return result
			return $result;
		}

		//Set alternates
		$result['alternates'] = [];

		//Set route
		$route = 'rapsysair_location_view';

		//Set route params
		$routeParams = ['id' => $id];

		//Iterate on each languages
		foreach($this->languages as $languageId => $language) {
			//Without current locale
			if ($languageId !== $locale) {
				//Set titles
				$titles = [];

				//Set route params locale
				$routeParams['_locale'] = $languageId;

				//Set route params location
				$routeParams['location'] = $this->slugger->slug($this->translator->trans($result['title'], [], null, $languageId));

				//Iterate on each locales
				foreach(array_keys($this->languages) as $other) {
					//Without other locale
					if ($other !== $languageId) {
						//Set other locale title
						$titles[$other] = $this->translator->trans($language, [], null, $other);
					}
				}

				//Add alternates locale
				$result['alternates'][substr($languageId, 0, 2)] = $result['alternates'][str_replace('_', '-', $languageId)] = [
					'absolute' => $this->router->generate($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($route, $routeParams),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($language, [], null, $languageId)
				];
			}
		}

		//Return result
		return [
			'id' => $result['id'],
			'city' => [
				'id' => $result['city_id'],
				'title' => $result['city'],
				'in' => $this->translator->trans('in '.$result['city']),
				'link' => $this->router->generate('rapsysair_city_view', ['city' => $result['city'], 'latitude' => $result['city_latitude'], 'longitude' => $result['city_longitude']])
			],
			'title' => $title = $this->translator->trans($result['title']),
			'latitude' => $result['latitude'],
			'longitude' => $result['longitude'],
			'indoor' => $result['indoor'],
			'modified' => $result['modified'],
			'around' => $this->translator->trans('around '.$result['title']),
			'at' => $this->translator->trans('at '.$result['title']),
			'atin' => $this->translator->trans('at '.$result['title']).' '.$this->translator->trans('in '.$result['city']),
			'multimap' => $this->translator->trans($result['title'].' sector map'),
			//XXX: Useless ???
			'slug' => $slug = $this->slugger->slug($title),
			'link' => $this->router->generate($route, ['_locale' => $locale, 'location' => $slug]+$routeParams),
			'alternates' => $result['alternates']
		];
	}

	/**
	 * Find complementary locations by session id
	 *
	 * @param int $id The session id
	 * @return array The other locations
	 */
	public function findComplementBySessionId(int $id): array {
		//Fetch complement locations
		return array_column(
			$this->getEntityManager()
				#->createQuery('SELECT l.id, l.title FROM Rapsys\AirBundle\Entity\Location l JOIN Rapsys\AirBundle\Entity\Session s WITH s.id = :sid LEFT JOIN Rapsys\AirBundle\Entity\Session s2 WITH s2.id != s.id AND s2.slot = s.slot AND s2.date = s.date WHERE l.id != s.location AND s2.location IS NULL GROUP BY l.id ORDER BY l.id')
				->createQuery('SELECT l.id, l.title FROM Rapsys\AirBundle\Entity\Session s LEFT JOIN Rapsys\AirBundle\Entity\Session s2 WITH s2.id != s.id AND s2.slot = s.slot AND s2.date = s.date LEFT JOIN Rapsys\AirBundle\Entity\Location l WITH l.id != s.location AND (l.id != s2.location OR s2.location IS NULL) WHERE s.id = :sid GROUP BY l.id ORDER BY l.id')
				->setParameter('sid', $id)
				->getArrayResult(),
			'id',
			'title'
		);
	}

	/**
	 * Find locations by user id
	 *
	 * @param int $id The user id
	 * @return array The user locations
	 */
	public function findByUserId(int $userId): array {
		//Set the request
		$req = 'SELECT l.id, l.title
FROM Rapsys\AirBundle\Entity\UserLocation AS ul
JOIN Rapsys\AirBundle\Entity\Location AS l ON (l.id = ul.location_id)
WHERE ul.user_id = :id';

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare result set for our request
		$rsm->addEntityResult('Rapsys\AirBundle\Entity\Location', 'l')
			->addFieldResult('l', 'id', 'id')
			->addFieldResult('l', 'title', 'title');

		//Send result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('id', $userId)
			->getResult();
	}
}
