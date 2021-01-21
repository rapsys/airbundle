<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query;

/**
 * SessionRepository
 */
class SessionRepository extends \Doctrine\ORM\EntityRepository {
	///Set accuweather max number of daily pages
	const ACCUWEATHER_DAILY = 12;

	///Set accuweather max number of hourly pages
	const ACCUWEATHER_HOURLY = 3;

	///Set guest delay
	const GUEST_DELAY = 2;

	///Set regular delay
	const REGULAR_DELAY = 3;

	///Set senior
	const SENIOR_DELAY = 4;

	///Set glyphs
	//TODO: document utf-8 codes ?
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
		'Stormy' => '☔' #2614
	];

	/**
	 * Find session by location, slot and date
	 *
	 * @param $location The location
	 * @param $slot The slot
	 * @param $date The datetime
	 */
	public function findOneByLocationSlotDate($location, $slot, $date) {
		//Return sessions
		return $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE (s.location = :location AND s.slot = :slot AND s.date = :date)')
			->setParameter('location', $location)
			->setParameter('slot', $slot)
			->setParameter('date', $date)
			->getSingleResult();
	}

	/**
	 * Find sessions by date period
	 *
	 * @param $period The date period
	 */
	public function findAllByDatePeriod($period) {
		//Return sessions
		return $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE s.date BETWEEN :begin AND :end')
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getResult();
	}

	/**
	 * Find sessions by location and date period
	 *
	 * @param $location The location
	 * @param $period The date period
	 */
	public function findAllByLocationDatePeriod($location, $period) {
		//Return sessions
		return $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE (s.location = :location AND s.date BETWEEN :begin AND :end)')
			->setParameter('location', $location)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getResult();
	}

	/**
	 * Find one session by location and user id within last month
	 *
	 * @param $location The location id
	 * @param $user The user id
	 */
	public function findOneWithinLastMonthByLocationUser($location, $user) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			"\t" => '',
			"\n" => ' '
		];

		//Set the request
		//XXX: give the gooddelay to guest just in case
		$req =<<<SQL
SELECT s.id
FROM RapsysAirBundle:Session s
JOIN RapsysAirBundle:Application a ON (a.id = s.application_id AND a.user_id = :uid AND (a.canceled IS NULL OR TIMESTAMPDIFF(DAY, a.canceled, ADDTIME(s.date, s.begin)) < 1))
WHERE s.location_id = :lid AND s.date >= DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 MONTH), INTERVAL :gooddelay DAY)
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		$rsm->addScalarResult('id', 'id', 'integer')
			->addIndexByScalar('id');

		//Return result
		return $em
			->createNativeQuery($req, $rsm)
			->setParameter('lid', $location)
			->setParameter('uid', $user)
			->setParameter('gooddelay', self::SENIOR_DELAY)
			->getOneOrNullResult();
	}

	/**
	 * Fetch session by id
	 *
	 * @param $id The session id
	 * @param $locale The locale
	 * @return array The session data
	 */
	public function fetchOneById($id, $locale = null) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:GroupUser' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Link' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Link'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Snippet' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Snippet'), $dp),
			'RapsysAirBundle:Slot' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Slot'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp),
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Set the request
		//TODO: compute scores ?
		//TODO: compute delivery date ? (J-3/J-4 ?)
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
	l.short AS l_short,
	l.title AS l_title,
	l.address AS l_address,
	l.zipcode AS l_zipcode,
	l.city AS l_city,
	l.latitude AS l_latitude,
	l.longitude AS l_longitude,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	p.id AS p_id,
	p.description AS p_description,
	GROUP_CONCAT(i.type ORDER BY i.id SEPARATOR "\\n") AS i_type,
	GROUP_CONCAT(i.url ORDER BY i.id SEPARATOR "\\n") AS i_url,
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
LEFT JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Snippet AS p ON (p.location_id = s.location_id AND p.user_id = a.user_id AND p.locale = :locale)
LEFT JOIN RapsysAirBundle:Link AS i ON (i.user_id = a.user_id)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
WHERE s.id = :sid
GROUP BY s.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

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
			->addScalarResult('l_short', 'l_short', 'string')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('l_address', 'l_address', 'string')
			->addScalarResult('l_zipcode', 'l_zipcode', 'string')
			->addScalarResult('l_city', 'l_city', 'string')
			->addScalarResult('l_latitude', 'l_latitude', 'float')
			->addScalarResult('l_longitude', 'l_longitude', 'float')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			->addScalarResult('i_type', 'i_type', 'string')
			->addScalarResult('i_url', 'i_url', 'string')
			->addScalarResult('p_id', 'p_id', 'integer')
			->addScalarResult('p_description', 'p_description', 'text')
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

		//Return result
		return $em
			->createNativeQuery($req, $rsm)
			->setParameter('sid', $id)
			->setParameter('locale', $locale)
			->getOneOrNullResult();
	}

	/**
	 * Fetch sessions calendar with translated location by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $locationId The location id
	 * @param $sessionId The session id
	 * @param $granted The session is granted
	 */
	public function fetchCalendarByDatePeriod(TranslatorInterface $translator, $period, $locationId = null, $sessionId = null, $granted = false) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:GroupUser' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Slot' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Slot'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp),
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Init granted sql
		$grantSql = '';

		//When granted is set
		if (empty($granted)) {
			//Set application and user as optional 
			$grantSql = 'LEFT ';
		}

		//Init location sql
		$locationSql = '';

		//When location id is set
		if (!empty($locationId)) {
			//Add location id clause
			$locationSql = "\n\t".'AND s.location_id = :lid';
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
	l.short AS l_short,
	l.title AS l_title,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\\n") AS sau_id,
	GROUP_CONCAT(sau.pseudonym ORDER BY sa.user_id SEPARATOR "\\n") AS sau_pseudonym
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
${grantSql}JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
${grantSql}JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
WHERE s.date BETWEEN :begin AND :end${locationSql}
GROUP BY s.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

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
			->addScalarResult('l_short', 'l_short', 'string')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_id', 'sau_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_pseudonym', 'sau_pseudonym', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate());

		//Add optional location id
		if (!empty($locationId)) {
			$res->setParameter('lid', $locationId);
		}

		//Get result
		$res = $res->getResult();

		//Init calendar
		$calendar = [];

		//Init month
		$month = null;

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $translator->trans($date->format('l')).' '.$date->format('d'),
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
						$class[] = 'granted';
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
						0 => $translator->trans($session['t_title']).' '.$translator->trans('at '.$session['l_title']).$translator->trans(':')
					];

					//Fetch pseudonyms from session applications
					$applications += array_combine(explode("\n", $session['sau_id']), array_map(function ($v) {return '- '.$v;}, explode("\n", $session['sau_pseudonym'])));

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

						//Set pseudonym
						$pseudonym = $session['au_pseudonym'].($count > 1 ? ' ['.$count.']':'');
					}

					//Add the session
					$calendar[$Ymd]['sessions'][$session['t_id'].sprintf('%02d', $session['l_id'])] = [
						'id' => $session['id'],
						'start' => $session['start'],
						'stop' => $session['stop'],
						'location' => $translator->trans($session['l_short']),
						'pseudonym' => $pseudonym,
						'class' => $class,
						'slot' => self::GLYPHS[$session['t_title']],
						'slottitle' => $translator->trans($session['t_title']),
						'weather' => $weather,
						'weathertitle' => implode(' ', $weathertitle),
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
	 * Fetch sessions calendar with translated location by date period and user
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $userId The user id
	 * @param $sessionId The session id
	 */
	public function fetchUserCalendarByDatePeriod(TranslatorInterface $translator, $period, $userId = null, $sessionId = null) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:GroupUser' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Slot' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Slot'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp),
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

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
	l.short AS l_short,
	l.title AS l_title,
	s.slot_id AS t_id,
	t.title AS t_title,
	s.application_id AS a_id,
	a.user_id AS au_id,
	au.pseudonym AS au_pseudonym,
	GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\\n") AS sau_id,
	GROUP_CONCAT(CONCAT("- ", sau.pseudonym) ORDER BY sa.user_id SEPARATOR "\\n") AS sau_pseudonym
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
${userJoinSql}LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
LEFT JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
WHERE s.date BETWEEN :begin AND :end${userWhereSql}
GROUP BY s.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

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
			->addScalarResult('l_short', 'l_short', 'string')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('au_id', 'au_id', 'integer')
			->addScalarResult('au_pseudonym', 'au_pseudonym', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_id', 'sau_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('sau_pseudonym', 'sau_pseudonym', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $em
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
				'title' => $translator->trans($date->format('l')).' '.$date->format('d'),
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
						0 => $translator->trans($session['t_title']).' '.$translator->trans('at '.$session['l_title']).$translator->trans(':')
					];

					//Fetch pseudonyms from session applications
					$applications += array_combine(explode("\n", $session['sau_id']), array_map(function ($v) {return '- '.$v;}, explode("\n", $session['sau_pseudonym'])));

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

						//Set pseudonym
						$pseudonym = $session['au_pseudonym'].($count > 1 ? ' ['.$count.']':'');
					}

					//Set title
					$title = $translator->trans($session['l_title']).($count > 1 ? ' ['.$count.']':'');

					//Add the session
					$calendar[$Ymd]['sessions'][$session['t_id'].sprintf('%02d', $session['l_id'])] = [
						'id' => $session['id'],
						'start' => $session['start'],
						'stop' => $session['stop'],
						'location' => $translator->trans($session['l_short']),
						'pseudonym' => $pseudonym,
						'class' => $class,
						'slot' => self::GLYPHS[$session['t_title']],
						'slottitle' => $translator->trans($session['t_title']),
						'weather' => $weather,
						'weathertitle' => implode(' ', $weathertitle),
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
	 * Find all session pending hourly weather
	 *
	 * @return array<Session> The sessions to update
	 */
	public function findAllPendingHourlyWeather() {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			//Accuweather
			':accuhourly' => self::ACCUWEATHER_HOURLY,
			//Delay
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Select all sessions starting and stopping in the next 3 days
		//XXX: select session starting after now and stopping before date(now)+3d as accuweather only provide hourly data for the next 3 days (INTERVAL 3 DAY)
		$req = <<<SQL
SELECT s.id, s.slot_id, s.location_id, s.date, s.begin, s.length, s.rainfall, s.rainrisk, s.realfeel, s.realfeelmin, s.realfeelmax, s.temperature, s.temperaturemin, s.temperaturemax, l.zipcode
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
WHERE ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) >= NOW() AND ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) < DATE(ADDDATE(NOW(), INTERVAL :accuhourly DAY))
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

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
		return $em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Find all session pending daily weather
	 *
	 * @return array<Session> The sessions to update
	 */
	public function findAllPendingDailyWeather() {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			//Accuweather
			':accudaily' => self::ACCUWEATHER_DAILY,
			':accuhourly' => self::ACCUWEATHER_HOURLY,
			//Delay
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Select all sessions stopping after next 3 days
		//XXX: select session stopping after or equal date(now)+3d as accuweather only provide hourly data for the next 3 days (INTERVAL 3 DAY)
		$req = <<<SQL
SELECT s.id, s.slot_id, s.location_id, s.date, s.begin, s.length, s.rainfall, s.rainrisk, s.realfeel, s.realfeelmin, s.realfeelmax, s.temperature, s.temperaturemin, s.temperaturemax, l.zipcode
FROM RapsysAirBundle:Session AS s
JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
WHERE ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) >= DATE(ADDDATE(NOW(), INTERVAL :accuhourly DAY)) AND ADDDATE(ADDTIME(ADDTIME(s.date, s.begin), s.length), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY) < DATE(ADDDATE(NOW(), INTERVAL :accudaily DAY))
SQL;

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

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
		return $em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Find every session pending application
	 *
	 * @return array<Session> The sessions to update
	 */
	public function findAllPendingApplication() {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			//Delay
			':regulardelay' => self::REGULAR_DELAY * 24 * 3600,
			':seniordelay' => self::SENIOR_DELAY * 24 * 3600,
			//Slot
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Select all sessions not locked without application or canceled application within attribution period
		//XXX: DIFF(start, now) <= IF(DIFF(start, created) <= SENIOR_DELAY in DAY, DIFF(start, created) * 3 / 4, SENIOR_DELAY)
		//TODO: remonter les données pour le mail ?
		$req =<<<SQL
SELECT s.id
FROM RapsysAirBundle:Session as s
LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id AND a.canceled IS NULL)
JOIN RapsysAirBundle:Application AS a2 ON (a2.session_id = s.id AND a2.canceled IS NULL)
WHERE s.locked IS NULL AND a.id IS NULL AND
TIME_TO_SEC(TIMEDIFF(@dt_start := ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY), NOW())) <= IF(
	TIME_TO_SEC(@td_sc := TIMEDIFF(@dt_start, s.created)) <= :seniordelay,
	ROUND(TIME_TO_SEC(@td_sc) * :regulardelay / :seniordelay),
	:seniordelay
)
GROUP BY s.id
ORDER BY @dt_start ASC, s.created ASC
SQL;


		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		$rsm = new ResultSetMapping();

		//Declare all fields
		$rsm
			->addEntityResult('RapsysAirBundle:Session', 's')
			->addFieldResult('s', 'id', 'id')
			->addIndexBy('s', 'id');

		//Send result
		return $em
			->createNativeQuery($req, $rsm)
			->getResult();
	}

	/**
	 * Fetch session best application by session id
	 *
	 * @param int $id The session id
	 * @return Application|null The application or null
	 */
	public function findBestApplicationById($id) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:Application' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:GroupUser' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Session'), $dp),
			//XXX: Set limit used to workaround mariadb subselect optimization
			':limit' => PHP_INT_MAX,
			//Delay
			':guestdelay' => self::GUEST_DELAY * 24 * 3600,
			':regulardelay' => self::REGULAR_DELAY * 24 * 3600,
			':seniordelay' => self::SENIOR_DELAY * 24 * 3600,
			//Group
			':guestid' => 2,
			':regularid' => 3,
			':seniorid' => 4,
			//Slot
			':afternoonid' => 2,
			':eveningid' => 3,
			':afterid' => 4,
			//XXX: days since last session after which guest regain normal priority
			':guestwait' => 30,
			//XXX: session count until considered at regular delay
			':scount' => 5,
			//XXX: pn_ratio over which considered at regular delay
			':pnratio' => 1,
			//XXX: tr_ratio diff over which considered at regular delay
			':trdiff' => 5,
			"\t" => '',
			"\n" => ' '
		];

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
		MAX(gu.group_id) AS group_id,
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
					TIME_TO_SEC(TIMEDIFF(ADDDATE(ADDTIME(s.date, s.begin), INTERVAL IF(s.slot_id = :afterid, 1, 0) DAY), NOW())) AS remaining,
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
	LEFT JOIN RapsysAirBundle:GroupUser AS gu ON (gu.user_id = d.user_id)
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
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Set update request
		$upreq = 'UPDATE RapsysAirBundle:Application SET score = :score, updated = NOW() WHERE id = :id';

		//Replace bundle entity name by table name
		$upreq = str_replace(array_keys($tables), array_values($tables), $upreq);

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
		$applications = $em
			->createNativeQuery($req, $rsm)
			->setParameter('sid', $id)
			//XXX: removed, we update score before returning best candidate
			//->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
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
			$em->getConnection()->executeUpdate($upreq, ['id' => $application->getId(), 'score' => $application->getScore()], ['id' => Type::INTEGER, 'score' => Type::FLOAT]);
		}

		//Return best ranked application
		return $ret;
	}
}
