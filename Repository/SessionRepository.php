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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Slot;

/**
 * SessionRepository
 */
class SessionRepository extends Repository {
	///Set glyphs
	//TODO: document utf-8 codes ?
	//TODO: use unknown == ? symbol by default ???
	//💃<= dancer #0001f483
	//💃<= tanguera #0001f483
	const GLYPHS = [
		//Slots
		'Morning' => '🌅', #0001f305
		'Afternoon' => '☀️', #2600
		'Evening' => '🌇', #0001f307
		'After' => '✨', #2728
		//Weathers
		'Cleary' => '☀', #2600
		'Sunny' => '⛅', #26c5
		'Cloudy' => '☁', #2601
		'Winty' => '❄️', #2744
		'Rainy' => '🌂', #0001f302
		'Stormy' => '☔', #2614
		//Rate
		'Euro' => '€', #20ac
		'Free' => '🍺', #0001f37a
		'Hat' => '🎩' #0001f3a9
	];

	/**
	 * Find session as array by id
	 *
	 * @param int $id The session id
	 * @return array The session data
	 */
	public function findOneByIdAsArray(int $id): ?array {
		//Set the request
		$req =<<<SQL
SELECT
	s.id,
	s.date,
	s.begin,
	ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS start,
	s.length,
	ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS stop,
	s.rainfall,
	s.rainrisk,
	s.realfeel,
	s.realfeelmin,
	s.realfeelmax,
	s.temperature,
	s.temperaturemin,
	s.temperaturemax,
	s.locked,
	s.created,
	s.updated,
	s.location_id AS l_id,
	l.title AS l_title,
	l.description AS l_description,
	l.address AS l_address,
	l.zipcode AS l_zipcode,
	l.city AS l_city,
	l.latitude AS l_latitude,
	l.longitude AS l_longitude,
	l.indoor AS l_indoor,
	l.updated AS l_updated,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	a.canceled AS a_canceled,
	a.dance_id AS ad_id,
	ad.name AS ad_name,
	ad.type AS ad_type,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	p.id AS p_id,
	p.description AS p_description,
	p.class AS p_class,
	p.contact AS p_contact,
	p.donate AS p_donate,
	p.link AS p_link,
	p.profile AS p_profile,
	p.rate AS p_rate,
	p.hat AS p_hat,
	GREATEST(COALESCE(s.updated, 0), COALESCE(l.updated, 0), COALESCE(t.updated, 0), COALESCE(p.updated, 0), COALESCE(MAX(sa.updated), 0), COALESCE(MAX(sau.updated), 0), COALESCE(MAX(sad.updated), 0)) AS modified,
	GROUP_CONCAT(sa.id ORDER BY sa.user_id SEPARATOR "\\n") AS sa_id,
	GROUP_CONCAT(IFNULL(sa.score, 'NULL') ORDER BY sa.user_id SEPARATOR "\\n") AS sa_score,
	GROUP_CONCAT(sa.created ORDER BY sa.user_id SEPARATOR "\\n") AS sa_created,
	GROUP_CONCAT(sa.updated ORDER BY sa.user_id SEPARATOR "\\n") AS sa_updated,
	GROUP_CONCAT(IFNULL(sa.canceled, 'NULL') ORDER BY sa.user_id SEPARATOR "\\n") AS sa_canceled,
	GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\\n") AS sau_id,
	GROUP_CONCAT(sau.pseudonym ORDER BY sa.user_id SEPARATOR "\\n") AS sau_pseudonym
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
LEFT JOIN RapsysAirBundle:Dance AS ad ON (ad.id = a.dance_id)
LEFT JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Snippet AS p ON (p.location_id = s.location_id AND p.user_id = a.user_id AND p.locale = :locale)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
LEFT JOIN RapsysAirBundle:Dance AS sad ON (sad.id = sa.dance_id)
WHERE s.id = :id
GROUP BY s.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('date', 'date', 'date')
			->addScalarResult('begin', 'begin', 'time')
			->addScalarResult('start', 'start', 'datetime')
			->addScalarResult('length', 'length', 'time')
			->addScalarResult('stop', 'stop', 'datetime')
			->addScalarResult('rainfall', 'rainfall', 'float')
			->addScalarResult('rainrisk', 'rainrisk', 'float')
			->addScalarResult('realfeel', 'realfeel', 'float')
			->addScalarResult('realfeelmin', 'realfeelmin', 'float')
			->addScalarResult('realfeelmax', 'realfeelmax', 'float')
			->addScalarResult('temperature', 'temperature', 'float')
			->addScalarResult('temperaturemin', 'temperaturemin', 'float')
			->addScalarResult('temperaturemax', 'temperaturemax', 'float')
			->addScalarResult('locked', 'locked', 'datetime')
			->addScalarResult('created', 'created', 'datetime')
			->addScalarResult('updated', 'updated', 'datetime')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('l_description', 'l_description', 'string')
			->addScalarResult('l_address', 'l_address', 'string')
			->addScalarResult('l_zipcode', 'l_zipcode', 'string')
			->addScalarResult('l_city', 'l_city', 'string')
			->addScalarResult('l_latitude', 'l_latitude', 'float')
			->addScalarResult('l_longitude', 'l_longitude', 'float')
			->addScalarResult('l_indoor', 'l_indoor', 'boolean')
			->addScalarResult('l_updated', 'l_updated', 'datetime')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('a_canceled', 'a_canceled', 'datetime')
			->addScalarResult('ad_id', 'ad_id', 'integer')
			->addScalarResult('ad_name', 'ad_name', 'string')
			->addScalarResult('ad_type', 'ad_type', 'string')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			->addScalarResult('p_id', 'p_id', 'integer')
			->addScalarResult('p_description', 'p_description', 'text')
			->addScalarResult('p_class', 'p_class', 'text')
			->addScalarResult('p_contact', 'p_contact', 'text')
			->addScalarResult('p_donate', 'p_donate', 'text')
			->addScalarResult('p_link', 'p_link', 'text')
			->addScalarResult('p_profile', 'p_profile', 'text')
			->addScalarResult('p_rate', 'p_rate', 'integer')
			->addScalarResult('p_hat', 'p_hat', 'boolean')
			->addScalarResult('modified', 'modified', 'datetime')
			//XXX: is a string because of \n separator
			->addScalarResult('sa_id', 'sa_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sa_score', 'sa_score', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sa_created', 'sa_created', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sa_updated', 'sa_updated', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sa_canceled', 'sa_canceled', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_id', 'sau_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_pseudonym', 'sau_pseudonym', 'string')
			->addIndexByScalar('id');

		//Set result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('id', $id)
			->getOneOrNullResult();

		//Without result
		if ($result === null) {
			//Return result
			return $result;
		}

		//Set route
		$route = 'rapsys_air_session_view';

		//Set route params
		$routeParams = ['id' => $id, 'location' => $this->slugger->slug($this->translator->trans($result['l_title']))];

		//Set session
		$session = [
			'id' => $id,
			'date' => $result['date'],
			'begin' => $result['begin'],
			'start' => $result['start'],
			'length' => $result['length'],
			'stop' => $result['stop'],
			'rainfall' => $result['rainfall'] !== null ? $result['rainfall'].' mm' : $result['rainfall'],
			'rainrisk' => $result['rainrisk'] !== null ? ($result['rainrisk']*100).' %' : $result['rainrisk'],
			'realfeel' => $result['realfeel'] !== null ? $result['realfeel'].' °C' : $result['realfeel'],
			'realfeelmin' => $result['realfeelmin'] !== null ? $result['realfeelmin'].' °C' : $result['realfeelmin'],
			'realfeelmax' => $result['realfeelmax'] !== null ? $result['realfeelmax'].' °C' : $result['realfeelmax'],
			'temperature' => $result['temperature'] !== null ? $result['temperature'].' °C' : $result['temperature'],
			'temperaturemin' => $result['temperaturemin'] !== null ? $result['temperaturemin'].' °C' : $result['temperaturemin'],
			'temperaturemax' => $result['temperaturemax'] !== null ? $result['temperaturemax'].' °C' : $result['temperaturemax'],
			'locked' => $result['locked'],
			'created' => $result['created'],
			'updated' => $result['updated'],
			'title' => $this->translator->trans('Session %id%', ['%id%' => $id]),
			'modified' => $result['modified'],
			'application' => null,
			'location' => [
				'id' => $result['l_id'],
				'at' => $this->translator->trans('at '.$result['l_title']),
				'title' => $locationTitle = $this->translator->trans($result['l_title']),
				'description' => $this->translator->trans($result['l_description']??'None'),
				'address' => $result['l_address'],
				'zipcode' => $result['l_zipcode'],
				'city' => $result['l_city'],
				'in' => $this->translator->trans('in '.$result['l_city']),
				'map' => $this->translator->trans($result['l_title'].' access map'),
				'multimap' => $this->translator->trans($result['l_title'].' sector map'),
				'latitude' => $result['l_latitude'],
				'longitude' => $result['l_longitude'],
				'indoor' => $result['l_indoor'],
				'slug' => $routeParams['location'],
				'link' => $this->router->generate('rapsys_air_location_view', ['id' => $result['l_id'], 'location' => $routeParams['location']])
			],
			'slot' => [
				'id' => $result['t_id'],
				'the' => $this->translator->trans('the '.lcfirst($result['t_title'])),
				'title' => $this->translator->trans($result['t_title'])
			],
			'snippet' => null,
			'applications' => null
		];

		//With application
		if (!empty($result['a_id'])) {
			$session['application'] = [
				'dance' => [
					'id' => $result['ad_id'],
					'title' => $this->translator->trans($result['ad_name'].' '.lcfirst($result['ad_type'])),
					'name' => $this->translator->trans($result['ad_name']),
					'type' => $this->translator->trans($result['ad_type']),
					'slug' => $routeParams['dance'] = $this->slugger->slug($this->translator->trans($result['ad_name'].' '.lcfirst($result['ad_type']))),
					'link' => $this->router->generate('rapsys_air_dance_view', ['id' => $result['ad_id'], 'name' => $this->slugger->slug($this->translator->trans($result['ad_name'])), 'type' => $this->slugger->slug($this->translator->trans($result['ad_type']))])
				],
				'user' => [
					'id' => $result['au_id'],
					'by' => $this->translator->trans('by %pseudonym%', [ '%pseudonym%' => $result['au_pseudonym'] ]),
					'title' => $result['au_pseudonym'],
					'slug' => $routeParams['user'] =  $this->slugger->slug($result['au_pseudonym']),
					'link' => $result['au_id'] == 1 && $routeParams['user'] == 'milonga-raphael' ? $this->router->generate('rapsys_air_user_milongaraphael') : $this->router->generate('rapsys_air_user_view', ['id' => $result['au_id'], 'user' => $routeParams['user']]),
					'contact' => $this->router->generate('rapsys_air_contact', ['id' => $result['au_id'], 'user' => $routeParams['user']])
				],
				'id' => $result['a_id'],
				'canceled' => $result['a_canceled']
			];
		}

		//With snippet
		if (!empty($result['p_id'])) {
			$session['snippet'] = [
				'id' => $result['p_id'],
				'description' => $result['p_description'],
				'class' => $result['p_class'],
				'contact' => $result['p_contact'],
				'donate' => $result['p_donate'],
				'link' => $result['p_link'],
				'profile' => $result['p_profile'],
				'rate' => $result['p_rate'],
				'hat' => $result['p_hat']
			];
		}

		//With applications
		if (!empty($result['sa_id'])) {
			//Extract applications id
			$result['sa_id'] = explode("\n", $result['sa_id']);
			//Extract applications score
			//XXX: score may be null before grant or for bad behaviour, replace NULL with 'NULL' to avoid silent drop in mysql
			$result['sa_score'] = array_map(function($v){return $v==='NULL'?null:$v;}, explode("\n", $result['sa_score']));
			//Extract applications created
			$result['sa_created'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $result['sa_created']));
			//Extract applications updated
			$result['sa_updated'] = array_map(function($v){return new \DateTime($v);}, explode("\n", $result['sa_updated']));
			//Extract applications canceled
			//XXX: canceled is null before cancelation, replace NULL with 'NULL' to avoid silent drop in mysql
			$result['sa_canceled'] = array_map(function($v){return $v==='NULL'?null:new \DateTime($v);}, explode("\n", $result['sa_canceled']));

			//Extract applications user id
			$result['sau_id'] = explode("\n", $result['sau_id']);
			//Extract applications user pseudonym
			$result['sau_pseudonym'] = explode("\n", $result['sau_pseudonym']);

			//Init applications
			$session['applications'] = [];

			//Iterate on each applications id
			foreach($result['sa_id'] as $i => $sa_id) {
				$session['applications'][$sa_id] = [
					'user' => null,
					'score' => $result['sa_score'][$i],
					'created' => $result['sa_created'][$i],
					'updated' => $result['sa_updated'][$i],
					'canceled' => $result['sa_canceled'][$i]
				];
				if (!empty($result['sau_id'][$i])) {
					$session['applications'][$sa_id]['user'] = [
						'id' => $result['sau_id'][$i],
						'title' => $result['sau_pseudonym'][$i],
						'slug' => $this->slugger->slug($result['sau_pseudonym'][$i])
					];
				}
			}
		}

		//Set link
		$session['link'] = $this->router->generate($route, $routeParams);

		//Set canonical
		$session['canonical'] = $this->router->generate($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL);

		//Set alternates
		$session['alternates'] = [];

		//Iterate on each locales
		foreach($this->translator->getFallbackLocales() as $fallback) {
			//Set titles
			$titles = [];

			//Set route params location
			$routeParams['location'] = $this->slugger->slug($this->translator->trans($result['l_title'], [], null, $fallback));

			//With route params dance
			if (!empty($routeParams['dance'])) {
			       $routeParams['dance'] = $this->slugger->slug($this->translator->trans($result['ad_name'].' '.lcfirst($result['ad_type']), [], null, $fallback));
			}

			//With route params user
			if (!empty($routeParams['user'])) {
			       $routeParams['user'] = $this->slugger->slug($result['au_pseudonym']);
			}

			//With current locale
			if ($fallback === $this->locale) {
				//Set current locale title
				$titles[$this->locale] = $this->translator->trans($this->languages[$this->locale]);
			//Without current locale
			} else {
				//Iterate on other locales
				foreach(array_diff($this->translator->getFallbackLocales(), [$fallback]) as $other) {
					//Set other locale title
					$titles[$other] = $this->translator->trans($this->languages[$fallback], [], null, $other);
				}

				//Add alternates locale
				$session['alternates'][str_replace('_', '-', $fallback)] = [
					'absolute' => $this->router->generate($route, ['_locale' => $fallback]+$routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($route, ['_locale' => $fallback]+$routeParams),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->languages[$fallback], [], null, $fallback)
				];
			}

			//Add alternates shorter locale
			if (empty($parameters['alternates'][$shortFallback = substr($fallback, 0, 2)])) {
				//Set locale locales context
				$session['alternates'][$shortFallback] = [
					'absolute' => $this->router->generate($route, ['_locale' => $fallback]+$routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($route, ['_locale' => $fallback]+$routeParams),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->languages[$fallback], [], null, $fallback)
				];
			}
		}

		//Return session
		return $session;
	}

	/**
	 * Find sessions as calendar array by date period
	 *
	 * @param DatePeriod $period The date period
	 * @param ?bool $granted The session is granted
	 * @param ?float $latitude The latitude
	 * @param ?float $longitude The longitude
	 * @param ?int $userId The user id
	 * @return array The session data
	 */
	public function findAllByPeriodAsCalendarArray(\DatePeriod $period, ?bool $granted = null, ?float $latitude = null, ?float $longitude = null, ?int $userId = null): array {
		//Init granted sql
		$grantSql = '';

		//When granted is set
		if (empty($granted)) {
			//Set application and user as optional
			$grantSql = 'LEFT ';
		}

		//Init location sql
		$locationSql = '';

		//When latitude and longitude
		if ($latitude !== null && $longitude !== null) {
			//Set the request
			//XXX: get every location between 0 and 15 km of latitude and longitude
			$req = <<<SQL
SELECT l.id
FROM RapsysAirBundle:Location AS l
WHERE ACOS(SIN(RADIANS(:latitude))*SIN(RADIANS(l.latitude))+COS(RADIANS(:latitude))*COS(RADIANS(l.latitude))*COS(RADIANS(:longitude - l.longitude)))*40030.17/2/PI() BETWEEN 0 AND 15
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
			       ->addIndexByScalar('id');

			//Set location ids
			//XXX: check that latitude and longitude have not be swapped !!!
			//XXX: latitude ~= 48.x longitude ~= 2.x
			$locationIds = array_keys(
				$this->_em
					->createNativeQuery($req, $rsm)
					->setParameter('latitude', $latitude)
					->setParameter('longitude', $longitude)
					->getArrayResult()
			);

			//Add location id clause
			$locationSql = "\n\t".'AND s.location_id IN (:lids)';
		//When user id
		} elseif ($userId !== null) {
			//Set the request
			//XXX: get every location between 0 and 15 km
			$req = <<<SQL
SELECT l2.id
FROM (
	SELECT l.id, l.latitude, l.longitude
	FROM RapsysAirBundle:Application AS a
	JOIN RapsysAirBundle:Session AS s ON (s.id = a.session_id)
	JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
	WHERE a.user_id = :id
	GROUP BY l.id
	ORDER BY NULL
	LIMIT 0, :limit
) AS a
JOIN RapsysAirBundle:Location AS l2
WHERE ACOS(SIN(RADIANS(a.latitude))*SIN(RADIANS(l2.latitude))+COS(RADIANS(a.latitude))*COS(RADIANS(l2.latitude))*COS(RADIANS(a.longitude - l2.longitude)))*40030.17/2/PI() BETWEEN 0 AND 15
GROUP BY l2.id
ORDER BY NULL
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
			       ->addIndexByScalar('id');

			//Set location ids
			$locationIds = array_keys(
				$this->_em
					->createNativeQuery($req, $rsm)
					->setParameter('id', $userId)
					->getArrayResult()
			);

			//With location ids
			if (!empty($locationIds)) {
				//Add location id clause
				$locationSql = "\n\t".'AND s.location_id IN (:lids)';
			}
		}

		//Set the request
		$req = <<<SQL

SELECT
	s.id,
	s.date,
	s.rainrisk,
	s.rainfall,
	s.realfeel,
	s.temperature,
	s.locked,
	ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS start,
	ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS stop,
	s.location_id AS l_id,
	l.title AS l_title,
	l.address AS l_address,
	l.zipcode AS l_zipcode,
	l.city AS l_city,
	l.latitude AS l_latitude,
	l.longitude AS l_longitude,
	l.indoor AS l_indoor,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	a.canceled AS a_canceled,
	a.dance_id AS ad_id,
	ad.name AS ad_name,
	ad.type AS ad_type,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	p.hat AS p_hat,
	p.rate AS p_rate,
	p.short AS p_short,
	GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\\n") AS sau_id,
	GROUP_CONCAT(sau.pseudonym ORDER BY sa.user_id SEPARATOR "\\n") AS sau_pseudonym,
	GROUP_CONCAT(sa.dance_id ORDER BY sa.user_id SEPARATOR "\\n") AS sad_id,
	GROUP_CONCAT(sad.name ORDER BY sa.user_id SEPARATOR "\\n") AS sad_name,
	GROUP_CONCAT(sad.type ORDER BY sa.user_id SEPARATOR "\\n") AS sad_type,
	GREATEST(COALESCE(s.updated, 0), COALESCE(l.updated, 0), COALESCE(p.updated, 0), COALESCE(MAX(sa.updated), 0), COALESCE(MAX(sau.updated), 0), COALESCE(MAX(sad.updated), 0)) AS modified
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
{$grantSql}JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
{$grantSql}JOIN RapsysAirBundle:Dance AS ad ON (ad.id = a.dance_id)
{$grantSql}JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Snippet AS p ON (p.location_id = s.location_id AND p.user_id = a.user_id AND p.locale = :locale)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:Dance AS sad ON (sad.id = sa.dance_id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
WHERE s.date BETWEEN :begin AND :end{$locationSql}
GROUP BY s.id
ORDER BY NULL
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
			->addScalarResult('date', 'date', 'date')
			->addScalarResult('rainrisk', 'rainrisk', 'float')
			->addScalarResult('rainfall', 'rainfall', 'float')
			->addScalarResult('realfeel', 'realfeel', 'float')
			->addScalarResult('temperature', 'temperature', 'float')
			->addScalarResult('locked', 'locked', 'datetime')
			->addScalarResult('start', 'start', 'datetime')
			->addScalarResult('stop', 'stop', 'datetime')
			->addScalarResult('modified', 'modified', 'datetime')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('l_address', 'l_address', 'string')
			->addScalarResult('l_zipcode', 'l_zipcode', 'string')
			->addScalarResult('l_city', 'l_city', 'string')
			->addScalarResult('l_latitude', 'l_latitude', 'float')
			->addScalarResult('l_longitude', 'l_longitude', 'float')
			->addScalarResult('l_indoor', 'l_indoor', 'boolean')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('a_canceled', 'a_canceled', 'datetime')
			->addScalarResult('ad_id', 'ad_id', 'string')
			->addScalarResult('ad_name', 'ad_name', 'string')
			->addScalarResult('ad_type', 'ad_type', 'string')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			->addScalarResult('p_hat', 'p_hat', 'boolean')
			->addScalarResult('p_rate', 'p_rate', 'integer')
			->addScalarResult('p_short', 'p_short', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_id', 'sau_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_pseudonym', 'sau_pseudonym', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sad_id', 'sad_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sad_name', 'sad_name', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sad_type', 'sad_type', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate());

		//Add optional location ids
		if (!empty($locationIds)) {
			$res->setParameter('lids', $locationIds);
		}

		//Get result
		$result = $res->getResult();

		//Init calendar
		$calendar = [];

		//Init month
		$month = null;

		//Set route
		$route = 'rapsys_air_session_view';

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $this->translator->trans($date->format('l')).' '.$date->format('d'),
				'modified' => null,
				'class' => [],
				'sessions' => []
			];

			//Detect month change
			if ($month != $date->format('m')) {
				$month = $date->format('m');
				//Append month for first day of month
				//XXX: except if today to avoid double add
				if ($date->format('U') != strtotime('today')) {
					$calendar[$Ymd]['title'] .= '/'.$month;
				}
			}
			//Deal with today
			if ($date->format('U') == ($today = strtotime('today'))) {
				$calendar[$Ymd]['title'] .= '/'.$month;
				$calendar[$Ymd]['current'] = true;
				$calendar[$Ymd]['class'][] = 'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] = 'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				#$calendar[$Ymd]['class'][] = 'next';
			}

			//Detect sunday
			if ($date->format('w') == 0) {
				$calendar[$Ymd]['class'][] = 'sunday';
			}

			//Iterate on each session to find the one of the day
			foreach($result as $session) {
				if (($sessionYmd = $session['date']->format('Ymd')) == $Ymd) {
					//With empty or greatest modified
					if ($calendar[$Ymd]['modified'] === null || $session['modified'] >= $calendar[$Ymd]['modified']) {
						//Update modified
						$calendar[$Ymd]['modified'] = $session['modified'];
					}

					//Set applications
					$applications = array_combine($candidates = explode("\n", $session['sau_id']), explode("\n", $session['sau_pseudonym']));

					//Compute classes
					$class = [];

					//With locked
					if (!empty($session['locked'])) {
						$class[] = 'locked';
					//Without locked
					} else {
						//With application
						if (!empty($session['a_id'])) {
							//With canceled session
							if (!empty($session['a_canceled'])) {
								$class[] = 'canceled';
							//With disputed session
							} elseif ($userId !== null && $session['au_id'] != $userId && !empty($candidates[$userId])) {
								$class[] = 'disputed';
							//Session is granted
							} else {
								$class[] = 'granted';
							}

							//With user id
							if ($userId !== null && $session['au_id'] == $userId) {
								$class[] = 'highlight';
							}
						} else {
							$class[] = 'pending';
						}

						//With latitude and longitude
						if ($latitude !== null && $longitude !== null && $session['l_latitude'] == $latitude && $session['l_longitude'] == $longitude) {
							$class[] = 'highlight';
						}
					}

					//Set temperature
					$temperature = [
						'glyph' => self::GLYPHS['Cleary'],
						'title' => []
					];

					//Compute temperature glyph
					//XXX: temperature may be null
					if ($session['temperature'] >= 17 && $session['temperature'] < 24) {
						$temperature['glyph'] = self::GLYPHS['Sunny'];
					} elseif ($session['temperature'] >= 10 && $session['temperature'] < 17) {
						$temperature['glyph'] = self::GLYPHS['Cloudy'];
					} elseif ($session['temperature'] !== null && $session['temperature'] < 10) {
						$temperature['glyph'] = self::GLYPHS['Winty'];
					}

					//Check if temperature is available
					if ($session['temperature'] !== null) {
						$temperature['title'][] = $session['temperature'].'°C';
					}

					//Check if realfeel is available
					if ($session['realfeel'] !== null) {
						$temperature['title'][] = $session['realfeel'].'°R';
					}

					//Compute temperature title
					$temperature['title'] = implode(' ', $temperature['title']);

					//Set rain
					$rain = [
						'glyph' => self::GLYPHS['Cleary'],
						'title' => []
					];

					//Compute rain glyph
					//XXX: rainfall and rainrisk may be null
					if ($session['rainrisk'] > 0.50 || $session['rainfall'] > 2) {
						$rain['glyph'] = self::GLYPHS['Stormy'];
					} elseif ($session['rainrisk'] > 0.40 || $session['rainfall'] > 1) {
						$rain['glyph'] = self::GLYPHS['Rainy'];
					}

					//Check if rainrisk is available
					if ($session['rainrisk'] !== null) {
						$rain['title'][] = ($session['rainrisk']*100).'%';
					}

					//Check if rainfall is available
					if ($session['rainfall'] !== null) {
						$rain['title'][] = $session['rainfall'].'mm';
					}

					//Compute rain title
					$rain['title'] = implode(' ', $rain['title']);

					//Set application
					$application = null;

					//Set rate
					$rate = null;

					//Set route params
					$routeParams = ['id' => $session['id'], 'location' => $this->slugger->slug($this->translator->trans($session['l_title']))];

					//With application
					if (!empty($session['a_id'])) {
						//Set dance
						$routeParams['dance'] = $this->slugger->slug($dance = $this->translator->trans($session['ad_name'].' '.lcfirst($session['ad_type'])));

						//Set user
						$routeParams['user'] =  $this->slugger->slug($session['au_pseudonym']);

						//Set title
						$title = $this->translator->trans('%dance% %id% by %pseudonym% %location% %city%', ['%dance%' => $dance, '%id%' => $session['id'], '%pseudonym%' => $session['au_pseudonym'], '%location%' => $this->translator->trans('at '.$session['l_title']), '%city%' => $this->translator->trans('in '.$session['l_city'])]);

						//Set pseudonym
						$application = [
							'dance' => [
								'id' => $session['ad_id'],
								'name' => $this->translator->trans($session['ad_name']),
								'type' => $this->translator->trans($session['ad_type']),
								'title' => $dance
							],
							'user' => [
								'id' => $session['au_id'],
								'title' => $session['au_pseudonym']
							]
						];

						//Set rate
						$rate = [
							'glyph' => self::GLYPHS['Free'],
							'rate' => null,
							'short' => $session['p_short'],
							'title' => $this->translator->trans('Free')
						];

						//With hat
						if (!empty($session['p_hat'])) {
							//Set glyph
							$rate['glyph'] = self::GLYPHS['Hat'];

							//With rate
							if (!empty($session['p_rate'])) {
								//Set rate
								$rate['rate'] = $session['p_rate'];

								//Set title
								$rate['title'] = $this->translator->trans('%rate%€ to the hat', ['%rate%' => $session['p_rate']]);
							//Without rate
							} else {
								//Set title
								$rate['title'] = $this->translator->trans('To the hat');
							}
						//With rate
						} elseif (!empty($session['p_rate'])) {
							//Set glyph
							$rate['glyph'] = self::GLYPHS['Euro'];

							//Set rate
							$rate['rate'] = $session['p_rate'];

							//Set title
							$rate['title'] = $session['p_rate'].' €';
						}
					//With unique application
					} elseif (count($applications) == 1) {
						//Set dance
						$dance = $this->translator->trans($session['sad_name'].' '.lcfirst($session['sad_type']));

						//Set title
						$title = $this->translator->trans('%dance% %id% by %pseudonym% %location% %city%', ['%dance%' => $dance, '%id%' => $session['id'], '%pseudonym%' => $session['sau_pseudonym'], '%location%' => $this->translator->trans('at '.$session['l_title']), '%city%' => $this->translator->trans('in '.$session['l_city'])]);

						//Set pseudonym
						$application = [
							'dance' => [
								'id' => $session['sad_id'],
								'name' => $this->translator->trans($session['sad_name']),
								'type' => $this->translator->trans($session['sad_type']),
								'title' => $dance
							],
							'user' => [
								'id' => $session['sau_id'],
								'title' => $session['sau_pseudonym']
							]
						];

						//TODO: glyph stuff ???
					//Without application
					} else {
						//Set title
						$title = $this->translator->trans('%slot% %id% %location%', ['%slot%' => $this->translator->trans($session['t_title']), '%id%' => $session['id'], '%location%' => $this->translator->trans('at '.$session['l_title'])]);
					}

					//Add the session
					$calendar[$Ymd]['sessions'][$session['t_id'].sprintf('%05d', $session['id'])] = [
						'id' => $session['id'],
						'start' => $session['start'],
						'stop' => $session['stop'],
						'class' => $class,
						'temperature' => $temperature,
						'rain' => $rain,
						'title' => $title,
						'link' => $this->router->generate($route, $routeParams),
						'location' => [
							'id' => $session['l_id'],
							'title' => $this->translator->trans($session['l_title']),
							'address' => $session['l_address'],
							'latitude' => $session['l_latitude'],
							'longitude' => $session['l_longitude'],
							'indoor' => $session['l_indoor'],
							'at' => $at = $this->translator->trans('at '.$session['l_title']),
							'in' => $in = $this->translator->trans('in '.$session['l_city']),
							'atin' => $at.' '.$in,
							'city' => $session['l_city'],
							'zipcode' => $session['l_zipcode']
						],
						'application' => $application,
						'slot' => [
							'glyph' => self::GLYPHS[$session['t_title']],
							'title' => $this->translator->trans($session['t_title'])
						],
						'rate' => $rate,
						'modified' => $session['modified'],
						'applications' => $applications
					];
				}
			}

			//Sort sessions
			ksort($calendar[$Ymd]['sessions']);
		}

		//Send result
		return $calendar;
	}

	/**
	 * Find session by location, slot and date
	 *
	 * @param Location $location The location
	 * @param Slot $slot The slot
	 * @param DateTime $date The datetime
	 * @return ?Session The found session
	 */
	public function findOneByLocationSlotDate(Location $location, Slot $slot, \DateTime $date): ?Session {
		//Return sessions
		return $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE (s.location = :location AND s.slot = :slot AND s.date = :date)')
			->setParameter('location', $location)
			->setParameter('slot', $slot)
			->setParameter('date', $date)
			->getSingleResult();
	}

	/**
	 * Fetch sessions by date period
	 *
	 * @XXX: used in calendar command
	 *
	 * @param DatePeriod $period The date period
	 * @return array The session array
	 */
	public function fetchAllByDatePeriod(\DatePeriod $period): array {
		//Set the request
		//TODO: exclude opera and others ?
		$req = <<<SQL
SELECT
	s.id,
	s.date,
	s.locked,
	s.updated,
	ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS start,
	ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS stop,
	s.location_id AS l_id,
	l.address AS l_address,
	l.zipcode AS l_zipcode,
	l.city AS l_city,
	l.title AS l_title,
	l.description AS l_description,
	l.latitude AS l_latitude,
	l.longitude AS l_longitude,
	s.application_id AS a_id,
	a.canceled AS a_canceled,
	ad.name AS ad_name,
	ad.type AS ad_type,
	a.user_id AS au_id,
	au.forename AS au_forename,
	au.pseudonym AS au_pseudonym,
	p.id AS p_id,
	p.description AS p_description,
	p.class AS p_class,
	p.short AS p_short,
	p.hat AS p_hat,
	p.rate AS p_rate,
	p.contact AS p_contact,
	p.donate AS p_donate,
	p.link AS p_link,
	p.profile AS p_profile
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
JOIN RapsysAirBundle:Dance AS ad ON (ad.id = a.dance_id)
JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Snippet AS p ON (p.location_id = s.location_id AND p.user_id = a.user_id AND p.locale = :locale)
WHERE s.date BETWEEN :begin AND :end
ORDER BY NULL
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
			->addScalarResult('date', 'date', 'date')
			->addScalarResult('locked', 'locked', 'datetime')
			->addScalarResult('updated', 'updated', 'datetime')
			->addScalarResult('start', 'start', 'datetime')
			->addScalarResult('stop', 'stop', 'datetime')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_address', 'l_address', 'string')
			->addScalarResult('l_zipcode', 'l_zipcode', 'string')
			->addScalarResult('l_city', 'l_city', 'string')
			->addScalarResult('l_latitude', 'l_latitude', 'float')
			->addScalarResult('l_longitude', 'l_longitude', 'float')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('l_description', 'l_description', 'string')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('a_canceled', 'a_canceled', 'datetime')
			->addScalarResult('ad_name', 'ad_name', 'string')
			->addScalarResult('ad_type', 'ad_type', 'string')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_forename', 'au_forename', 'string')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			->addScalarResult('p_id', 'p_id', 'integer')
			->addScalarResult('p_description', 'p_description', 'string')
			->addScalarResult('p_class', 'p_class', 'string')
			->addScalarResult('p_short', 'p_short', 'string')
			->addScalarResult('p_hat', 'p_hat', 'integer')
			->addScalarResult('p_rate', 'p_rate', 'integer')
			->addScalarResult('p_contact', 'p_contact', 'string')
			->addScalarResult('p_donate', 'p_donate', 'string')
			->addScalarResult('p_link', 'p_link', 'string')
			->addScalarResult('p_profile', 'p_profile', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate());

		//Return result
		return $res->getResult();
	}

	/**
	 * Fetch sessions calendar with translated location by date period and user
	 *
	 * @param DatePeriod $period The date period
	 * @param ?int $userId The user id
	 * @param ?int $sessionId The session id
	 */
	public function fetchUserCalendarByDatePeriod(\DatePeriod $period, ?int $userId = null, ?int $sessionId = null): array {
		//Init user sql
		$userJoinSql = $userWhereSql = '';

		//When user id is set
		if (!empty($userId)) {
			//Add user join
			$userJoinSql = 'JOIN RapsysAirBundle:Application AS sua ON (sua.session_id = s.id)'."\n";
			//Add user id clause
			$userWhereSql = "\n\t".'AND sua.user_id = :uid';
		}

		//Set the request
		//TODO: change as_u_* in sau_*, a_u_* in au_*, etc, see request up
		$req = <<<SQL
SELECT
	s.id,
	s.date,
	s.rainrisk,
	s.rainfall,
	s.realfeel,
	s.temperature,
	s.locked,
	ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS start,
	ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) AS stop,
	s.location_id AS l_id,
	l.title AS l_title,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	ad.name AS ad_name,
	ad.type AS ad_type,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	p.rate AS p_rate,
	p.hat AS p_hat,
	GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\\n") AS sau_id,
	GROUP_CONCAT(CONCAT("- ", sau.pseudonym) ORDER BY sa.user_id SEPARATOR "\\n") AS sau_pseudonym
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
{$userJoinSql}LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
LEFT JOIN RapsysAirBundle:Snippet AS p ON (p.location_id = s.location_id AND p.user_id = a.user_id AND p.locale = :locale)
LEFT JOIN RapsysAirBundle:Dance AS ad ON (ad.id = a.dance_id)
LEFT JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
WHERE s.date BETWEEN :begin AND :end{$userWhereSql}
GROUP BY s.id
ORDER BY NULL
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
			->addScalarResult('date', 'date', 'date')
			->addScalarResult('rainrisk', 'rainrisk', 'float')
			->addScalarResult('rainfall', 'rainfall', 'float')
			->addScalarResult('realfeel', 'realfeel', 'float')
			->addScalarResult('temperature', 'temperature', 'float')
			->addScalarResult('locked', 'locked', 'datetime')
			->addScalarResult('start', 'start', 'datetime')
			->addScalarResult('stop', 'stop', 'datetime')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('ad_name', 'ad_name', 'string')
			->addScalarResult('ad_type', 'ad_type', 'string')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			->addScalarResult('p_rate', 'p_rate', 'integer')
			->addScalarResult('p_hat', 'p_hat', 'boolean')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_id', 'sau_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_pseudonym', 'sau_pseudonym', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->setParameter('uid', $userId)
			->getResult();

		//Init calendar
		$calendar = [];

		//Init month
		$month = null;

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $this->translator->trans($date->format('l')).' '.$date->format('d'),
				'class' => [],
				'sessions' => []
			];

			//Detect month change
			if ($month != $date->format('m')) {
				$month = $date->format('m');
				//Append month for first day of month
				//XXX: except if today to avoid double add
				if ($date->format('U') != strtotime('today')) {
					$calendar[$Ymd]['title'] .= '/'.$month;
				}
			}
			//Deal with today
			if ($date->format('U') == ($today = strtotime('today'))) {
				$calendar[$Ymd]['title'] .= '/'.$month;
				$calendar[$Ymd]['current'] = true;
				$calendar[$Ymd]['class'][] = 'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] = 'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				#$calendar[$Ymd]['class'][] = 'next';
			}

			//Detect sunday
			if ($date->format('w') == 0) {
				$calendar[$Ymd]['class'][] = 'sunday';
			}

			//Iterate on each session to find the one of the day
			foreach($res as $session) {
				if (($sessionYmd = $session['date']->format('Ymd')) == $Ymd) {
					//Count number of application
					$count = count(explode("\n", $session['sau_id']));

					//Compute classes
					$class = [];
					if (!empty($session['a_id'])) {
						$applications = [ $session['au_id'] => $session['au_pseudonym'] ];
						if ($session['au_id'] == $userId) {
							$class[] = 'granted';
						} else {
							$class[] = 'disputed';
						}
					} elseif ($count > 1) {
						$class[] = 'disputed';
					} elseif (!empty($session['locked'])) {
						$class[] = 'locked';
					} else {
						$class[] = 'pending';
					}

					if ($sessionId == $session['id']) {
						$class[] = 'highlight';
					}

					//Set temperature
					//XXX: realfeel may be null, temperature should not
					$temperature = $session['realfeel'] !== null ? $session['realfeel'] : $session['temperature'];

					//Compute weather
					//XXX: rainfall may be null
					if ($session['rainrisk'] > 0.50 || $session['rainfall'] > 2) {
						$weather = self::GLYPHS['Stormy'];
					} elseif ($session['rainrisk'] > 0.40 || $session['rainfall'] > 1) {
						$weather = self::GLYPHS['Rainy'];
					} elseif ($temperature > 24) {
						$weather = self::GLYPHS['Cleary'];
					} elseif ($temperature > 17) {
						$weather = self::GLYPHS['Sunny'];
					} elseif ($temperature > 10) {
						$weather = self::GLYPHS['Cloudy'];
					} elseif ($temperature !== null) {
						$weather = self::GLYPHS['Winty'];
					} else {
						$weather = null;
					}

					//Init weathertitle
					$weathertitle = [];

					//Check if realfeel is available
					if ($session['realfeel'] !== null) {
						$weathertitle[] = $session['realfeel'].'°R';
					}

					//Check if temperature is available
					if ($session['temperature'] !== null) {
						$weathertitle[] = $session['temperature'].'°C';
					}

					//Check if rainrisk is available
					if ($session['rainrisk'] !== null) {
						$weathertitle[] = ($session['rainrisk']*100).'%';
					}

					//Check if rainfall is available
					if ($session['rainfall'] !== null) {
						$weathertitle[] = $session['rainfall'].'mm';
					}

					//Set applications
					$applications = [
						0 => $this->translator->trans($session['t_title']).' '.$this->translator->trans('at '.$session['l_title']).$this->translator->trans(':')
					];

					//Fetch pseudonyms from session applications
					$applications += array_combine(explode("\n", $session['sau_id']), array_map(function ($v) {return '- '.$v;}, explode("\n", $session['sau_pseudonym'])));

					//Set dance
					$dance = null;

					//Set pseudonym
					$pseudonym = null;

					//Check that session is not granted
					if (empty($session['a_id'])) {
						//With location id and unique application
						if ($count == 1) {
							//Set unique application pseudonym
							$pseudonym = $session['sau_pseudonym'];
						}
					//Session is granted
					} else {
						//Replace granted application
						$applications[$session['au_id']] = '* '.$session['au_pseudonym'];

						//Set dance
						$dance = $this->translator->trans($session['ad_name'].' '.lcfirst($session['ad_type']));

						//Set pseudonym
						$pseudonym = $session['au_pseudonym'].($count > 1 ? ' ['.$count.']':'');
					}

					//Set title
					$title = $this->translator->trans($session['l_title']).($count > 1 ? ' ['.$count.']':'');

					//Add the session
					$calendar[$Ymd]['sessions'][$session['t_id'].sprintf('%02d', $session['l_id'])] = [
						'id' => $session['id'],
						'start' => $session['start'],
						'stop' => $session['stop'],
						'location' => $this->translator->trans($session['l_title']),
						'dance' => $dance,
						'pseudonym' => $pseudonym,
						'class' => $class,
						'slot' => self::GLYPHS[$session['t_title']],
						'slottitle' => $this->translator->trans($session['t_title']),
						'weather' => $weather,
						'weathertitle' => implode(' ', $weathertitle),
						'applications' => $applications,
						'rate' => $session['p_rate'],
						'hat' => $session['p_hat']
					];
				}
			}

			//Sort sessions
			ksort($calendar[$Ymd]['sessions']);
		}

		//Send result
		return $calendar;
	}

	/**
	 * Find all session pending hourly weather
	 *
	 * @return array The sessions to update
	 */
	public function findAllPendingHourlyWeather(): array {
		//Select all sessions starting and stopping in the next 3 days
		//XXX: select session starting after now and stopping before date(now)+3d as accuweather only provide hourly data for the next 3 days (INTERVAL 3 DAY)
		$req = <<<SQL
SELECT s.id, s.slot_id, s.location_id, s.date, s.begin, s.length, s.rainfall, s.rainrisk, s.realfeel, s.realfeelmin, s.realfeelmax, s.temperature, s.temperaturemin, s.temperaturemax, l.zipcode
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
WHERE ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) >= NOW() AND ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) < DATE(ADDDATE(NOW(), INTERVAL :accuhourly DAY))
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		$rsm
			->addEntityResult('RapsysAirBundle:Session', 's')
			->addFieldResult('s', 'id', 'id')
			->addFieldResult('s', 'date', 'date')
			->addFieldResult('s', 'begin', 'begin')
			->addFieldResult('s', 'length', 'length')
			->addFieldResult('s', 'rainfall', 'rainfall')
			->addFieldResult('s', 'rainrisk', 'rainrisk')
			->addFieldResult('s', 'realfeel', 'realfeel')
			->addFieldResult('s', 'realfeelmin', 'realfeelmin')
			->addFieldResult('s', 'realfeelmax', 'realfeelmax')
			->addFieldResult('s', 'temperature', 'temperature')
			->addFieldResult('s', 'temperaturemin', 'temperaturemin')
			->addFieldResult('s', 'temperaturemax', 'temperaturemax')
			->addJoinedEntityResult('RapsysAirBundle:Slot', 'o', 's', 'slot')
			->addFieldResult('o', 'slot_id', 'id')
			->addJoinedEntityResult('RapsysAirBundle:Location', 'l', 's', 'location')
			->addFieldResult('l', 'location_id', 'id')
			->addFieldResult('l', 'zipcode', 'zipcode')
			->addIndexBy('s', 'id');

		//Send result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Find all session pending daily weather
	 *
	 * @return array The sessions to update
	 */
	public function findAllPendingDailyWeather(): array {
		//Select all sessions stopping after next 3 days
		//XXX: select session stopping after or equal date(now)+3d as accuweather only provide hourly data for the next 3 days (INTERVAL 3 DAY)
		$req = <<<SQL
SELECT s.id, s.slot_id, s.location_id, s.date, s.begin, s.length, s.rainfall, s.rainrisk, s.realfeel, s.realfeelmin, s.realfeelmax, s.temperature, s.temperaturemin, s.temperaturemax, l.zipcode
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
WHERE ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) >= DATE(ADDDATE(NOW(), INTERVAL :accuhourly DAY)) AND ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) < DATE(ADDDATE(NOW(), INTERVAL :accudaily DAY))
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		$rsm
			->addEntityResult('RapsysAirBundle:Session', 's')
			->addFieldResult('s', 'id', 'id')
			->addFieldResult('s', 'date', 'date')
			->addFieldResult('s', 'begin', 'begin')
			->addFieldResult('s', 'length', 'length')
			->addFieldResult('s', 'rainfall', 'rainfall')
			->addFieldResult('s', 'rainrisk', 'rainrisk')
			->addFieldResult('s', 'realfeel', 'realfeel')
			->addFieldResult('s', 'realfeelmin', 'realfeelmin')
			->addFieldResult('s', 'realfeelmax', 'realfeelmax')
			->addFieldResult('s', 'temperature', 'temperature')
			->addFieldResult('s', 'temperaturemin', 'temperaturemin')
			->addFieldResult('s', 'temperaturemax', 'temperaturemax')
			->addJoinedEntityResult('RapsysAirBundle:Slot', 'o', 's', 'slot')
			->addFieldResult('o', 'slot_id', 'id')
			->addJoinedEntityResult('RapsysAirBundle:Location', 'l', 's', 'location')
			->addFieldResult('l', 'location_id', 'id')
			->addFieldResult('l', 'zipcode', 'zipcode')
			->addIndexBy('s', 'id');

		//Send result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Find every session pending application
	 *
	 * @return array The sessions to update
	 */
	public function findAllPendingApplication(): array {
		//Select all sessions not locked without application or canceled application within attribution period
		//XXX: DIFF(start, now) <= IF(DIFF(start, created) <= SENIOR_DELAY in DAY, DIFF(start, created) * 3 / 4, SENIOR_DELAY)
		//TODO: remonter les données pour le mail ?
		$req =<<<SQL
SELECT s.id
FROM RapsysAirBundle:Session as s
LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id AND a.canceled IS NULL)
JOIN RapsysAirBundle:Application AS a2 ON (a2.session_id = s.id AND a2.canceled IS NULL)
WHERE s.locked IS NULL AND s.application_id IS NULL AND
(UNIX_TIMESTAMP(@dt_start := ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY)) - UNIX_TIMESTAMP()) <= IF(
	(@td_sc := UNIX_TIMESTAMP(@dt_start) - UNIX_TIMESTAMP(s.created)) <= :seniordelay,
	ROUND(@td_sc * :regulardelay / :seniordelay),
	:seniordelay
)
GROUP BY s.id
ORDER BY @dt_start ASC, s.created ASC
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		$rsm
			->addEntityResult('RapsysAirBundle:Session', 's')
			->addFieldResult('s', 'id', 'id')
			->addIndexBy('s', 'id');

		//Send result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Fetch session best application by session id
	 *
	 * @param int $id The session id
	 * @return ?Application The application or null
	 */
	public function findBestApplicationById(int $id): ?Application {
		/**
		 * Query session applications ranked by location score, global score, created and user_id
		 *
		 * @xxx guest (or less) with application on location within 30 day are only considered within guestdelay
		 *
		 * @xxx regular (or less) premium application on hotspot are only considered within regulardelay
		 *
		 * @xxx senior (or less) with 5 or less session on location are only considered within seniordelay
		 *
		 * @xxx senior (or less) with l_pn_ratio >= 1 are only considered within seniordelay
		 *
		 * @xxx senior (or less) with l_tr_ratio >= (o_tr_ratio + 5) are only considered within seniordelay
		 *
		 * @xxx only consider session within one year (may be unaccurate by the day with after session)
		 *
		 * @xxx rainfall may not be accessible for previous session and other session at d-4 (only at d-2)
		 *
		 * @todo ??? feedback the data to inform the rejected users ???
		 */
		$req = <<<SQL
SELECT e.id, e.l_score AS score
FROM (
	SELECT
		d.id,
		d.user_id,
		d.l_count,
		d.l_score,
		d.l_tr_ratio,
		d.l_pn_ratio,
		d.l_previous,
		d.g_score,
		d.o_tr_ratio,
		MAX(ug.group_id) AS group_id,
		d.remaining,
		d.premium,
		d.hotspot,
		d.created
	FROM (
		SELECT
			c.id,
			c.user_id,
			c.l_count,
			c.l_score,
			c.l_tr_ratio,
			c.l_pn_ratio,
			c.l_previous,
			c.g_score,
			AVG(IF(a4.id IS NOT NULL AND s4.temperature IS NOT NULL AND s4.rainfall IS NOT NULL, s4.temperature/(1+s4.rainfall), NULL)) AS o_tr_ratio,
			c.remaining,
			c.premium,
			c.hotspot,
			c.created
		FROM (
			SELECT
				b.id,
				b.user_id,
				b.session_id,
				b.date,
				b.location_id,
				b.l_count,
				b.l_score,
				b.l_tr_ratio,
				b.l_pn_ratio,
				b.l_previous,
				SUM(IF(a3.id IS NOT NULL, 1/ABS(DATEDIFF(ADDDATE(b.date, INTERVAL IF(b.slot_id = :afterid, 1, 0) DAY), ADDDATE(s3.date, INTERVAL IF(s3.slot_id = :afterid, 1, 0) DAY))), 0)) AS g_score,
				b.remaining,
				b.premium,
				b.hotspot,
				b.created
			FROM (
				SELECT
					a.id,
					a.user_id,
					s.id AS session_id,
					s.date AS date,
					s.slot_id,
					s.location_id,
					COUNT(a2.id) AS l_count,
					SUM(IF(a2.id IS NOT NULL, 1/ABS(DATEDIFF(ADDDATE(s.date, INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY), ADDDATE(s2.date, INTERVAL IF(s2.slot_id = :afterid, 1, 0) DAY))), 0)) AS l_score,
					AVG(IF(a2.id IS NOT NULL AND s2.temperature IS NOT NULL AND s2.rainfall IS NOT NULL, s2.temperature/(1+s2.rainfall), NULL)) AS l_tr_ratio,
					(SUM(IF(a2.id IS NOT NULL AND s2.premium = 1, 1, 0))+1)/(SUM(IF(a2.id IS NOT NULL AND s2.premium = 0, 1, 0))+1) AS l_pn_ratio,
					MIN(IF(a2.id IS NOT NULL, DATEDIFF(ADDDATE(s.date, INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY), ADDDATE(s2.date, INTERVAL IF(s2.slot_id = :afterid, 1, 0) DAY)), NULL)) AS l_previous,
					UNIX_TIMESTAMP(ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY)) - UNIX_TIMESTAMP() AS remaining,
					s.premium,
					l.hotspot,
					a.created
				FROM RapsysAirBundle:Session AS s
				JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
				JOIN RapsysAirBundle:Application AS a ON (a.session_id = s.id AND a.canceled IS NULL)
				LEFT JOIN RapsysAirBundle:Session AS s2 ON (s2.id != s.id AND s2.location_id = s.location_id AND s2.slot_id IN (:afternoonid, :eveningid) AND s2.application_id IS NOT NULL AND s2.locked IS NULL AND s2.date > s.date - INTERVAL 1 YEAR)
				LEFT JOIN RapsysAirBundle:Application AS a2 ON (a2.id = s2.application_id AND a2.user_id = a.user_id AND (a2.canceled IS NULL OR TIMESTAMPDIFF(DAY, a2.canceled, ADDDATE(ADDTIME(s2.date, s2.begin), INTERVAL IF(s2.slot_id = :afterid, 1, 0) DAY)) < 1))
				WHERE s.id = :sid
				GROUP BY a.id
				ORDER BY NULL
				LIMIT 0, :limit
			) AS b
			LEFT JOIN RapsysAirBundle:Session AS s3 ON (s3.id != b.session_id AND s3.application_id IS NOT NULL AND s3.locked IS NULL AND s3.date > b.date - INTERVAL 1 YEAR)
			LEFT JOIN RapsysAirBundle:Application AS a3 ON (a3.id = s3.application_id AND a3.user_id = b.user_id AND (a3.canceled IS NULL OR TIMESTAMPDIFF(DAY, a3.canceled, ADDDATE(ADDTIME(s3.date, s3.begin), INTERVAL IF(s3.slot_id = :afterid, 1, 0) DAY)) < 1))
			GROUP BY b.id
			ORDER BY NULL
			LIMIT 0, :limit
		) AS c
		LEFT JOIN RapsysAirBundle:Session AS s4 ON (s4.id != c.session_id AND s4.location_id = c.location_id AND s4.application_id IS NOT NULL AND s4.locked IS NULL AND s4.date > c.date - INTERVAL 1 YEAR)
		LEFT JOIN RapsysAirBundle:Application AS a4 ON (a4.id = s4.application_id AND a4.user_id != c.user_id AND (a4.canceled IS NULL OR TIMESTAMPDIFF(DAY, a4.canceled, ADDDATE(ADDTIME(s4.date, s4.begin), INTERVAL IF(s4.slot_id = :afterid, 1, 0) DAY)) < 1))
		GROUP BY c.id
		ORDER BY NULL
		LIMIT 0, :limit
	) AS d
	LEFT JOIN RapsysAirBundle:UserGroup AS ug ON (ug.user_id = d.user_id)
	GROUP BY d.id
	LIMIT 0, :limit
) AS e
WHERE
	IF(e.group_id <= :guestid AND e.l_previous <= :guestwait, e.remaining <= :guestdelay, 1) AND
	IF(e.group_id <= :regularid AND e.premium = 1 AND e.hotspot = 1, e.remaining <= :regulardelay, 1) AND
	IF(e.group_id <= :seniorid AND e.l_count <= :scount, e.remaining <= :regulardelay, 1) AND
	IF(e.group_id <= :seniorid AND e.l_pn_ratio >= :pnratio, e.remaining <= :regulardelay, 1) AND
	IF(e.group_id <= :seniorid AND e.l_tr_ratio >= (e.o_tr_ratio + :trdiff), e.remaining <= :regulardelay, 1)
ORDER BY e.l_score ASC, e.g_score ASC, e.created ASC, e.user_id ASC
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Set update request
		$upreq = 'UPDATE RapsysAirBundle:Application SET score = :score, updated = NOW() WHERE id = :id';

		//Replace bundle entity name by table name
		$upreq = str_replace($this->tableKeys, $this->tableValues, $upreq);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		$rsm
			->addEntityResult('RapsysAirBundle:Application', 'a')
			->addFieldResult('a', 'id', 'id')
			->addFieldResult('a', 'score', 'score')
			->addIndexBy('a', 'id');

		//Get result
		//XXX: setting limit in subqueries is required to prevent mariadb optimisation
		$applications = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('sid', $id)
			->getResult();

		//Init ret
		$ret = null;

		//Update score
		foreach($applications as $application) {
			//Check if we already saved best candidate
			if ($ret === null) {
				//Return first application
				$ret = $application;
			}

			//Update application updated field
			//XXX: updated field is not modified for user with bad behaviour as application is not retrieved until delay is reached
			$this->_em->getConnection()->executeUpdate($upreq, ['id' => $application->getId(), 'score' => $application->getScore()], ['id' => Types::INTEGER, 'score' => Types::FLOAT]);
		}

		//Return best ranked application
		return $ret;
	}

	/**
	 * Rekey sessions and applications by chronological session id
	 *
	 * @return bool The rekey success or failure
	 */
	function rekey(): bool {
		//Get connection
		$cnx = $this->_em->getConnection();

		//Set the request
		$req = <<<SQL
SELECT
	a.id,
	a.sa_id
FROM (
	SELECT
		s.id,
		s.date,
		s.begin,
		s.slot_id,
		GROUP_CONCAT(sa.id ORDER BY sa.id SEPARATOR "\\n") AS sa_id
	FROM RapsysAirBundle:Session AS s
	LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
	GROUP BY s.id
	ORDER BY NULL
) AS a
ORDER BY ADDDATE(ADDTIME(a.date, a.begin), INTERVAL IF(a.slot_id = :afterid, 1, 0) DAY) ASC
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
			->addScalarResult('sa_id', 'sa_id', 'string');
			#->addIndexByScalar('id');

		//Fetch result
		$rnq = $this->_em->createNativeQuery($req, $rsm);

		//Get result set
		$res = $rnq->getResult();

		//Start transaction
		$cnx->beginTransaction();

		//Set update session request
		$sreq = <<<SQL
UPDATE RapsysAirBundle:Session
SET id = :nid, updated = NOW()
WHERE id = :id
SQL;

		//Replace bundle entity name by table name
		$sreq = str_replace($this->tableKeys, $this->tableValues, $sreq);

		//Set update application request
		$areq = <<<SQL
UPDATE RapsysAirBundle:Application
SET session_id = :nid, updated = NOW()
WHERE session_id = :id
SQL;

		//Replace bundle entity name by table name
		$areq = str_replace($this->tableKeys, $this->tableValues, $areq);

		//Set max value
		$max = max(array_keys($res));

		try {
			//Prepare session to update
			foreach($res as $id => $data) {
				//Set temp id
				$res[$id]['t_id'] = $max + $id + 1;

				//Set new id
				$res[$id]['n_id'] = $id + 1;

				//Explode application ids
				$res[$id]['sa_id'] = explode("\n", $data['sa_id']);

				//Without change
				if ($res[$id]['n_id'] == $res[$id]['id']) {
					//Remove unchanged session
					unset($res[$id]);
				}
			}

			//With changes
			if (!empty($res)) {
				//Disable foreign key checks
				$cnx->prepare('SET foreign_key_checks = 0')->execute();

				//Update to temp id
				foreach($res as $id => $data) {
					//Run session update
					$cnx->executeUpdate($sreq, ['nid' => $res[$id]['t_id'], 'id' => $res[$id]['id']]);

					//Run applications update
					$cnx->executeUpdate($areq, ['nid' => $res[$id]['t_id'], 'id' => $res[$id]['id']]);
				}

				//Update to new id
				foreach($res as $id => $data) {
					//Run session update
					$cnx->executeUpdate($sreq, ['nid' => $res[$id]['n_id'], 'id' => $res[$id]['t_id']]);

					//Run applications update
					$cnx->executeUpdate($areq, ['nid' => $res[$id]['n_id'], 'id' => $res[$id]['t_id']]);
				}

				//Restore foreign key checks
				$cnx->prepare('SET foreign_key_checks = 1')->execute();

				//Commit transaction
				$cnx->commit();

				//Set update auto_increment request
				$ireq = <<<SQL
ALTER TABLE RapsysAirBundle:Session
auto_increment = 1
SQL;

				//Replace bundle entity name by table name
				$ireq = str_replace($this->tableKeys, $this->tableValues, $ireq);

				//Reset auto_increment
				$cnx->exec($ireq);
			//Without changes
			} else {
				//Rollback transaction
				$cnx->rollback();
			}
		} catch(\Exception $e) {
			//Rollback transaction
			$cnx->rollback();

			//Throw exception
			throw $e;
		}

		//Return success
		return true;
	}
}
