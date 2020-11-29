<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * SessionRepository
 */
class SessionRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find session by location, slot and date
	 *
	 * @param $location The location
	 * @param $slot The slot
	 * @param $date The datetime
	 */
	public function findOneByLocationSlotDate($location, $slot, $date) {
		//Fetch session
		$ret = $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE (s.location = :location AND s.slot = :slot AND s.date = :date)')
			->setParameter('location', $location)
			->setParameter('slot', $slot)
			->setParameter('date', $date)
			->getSingleResult();

		//Send result
		return $ret;
	}

	/**
	 * Find sessions by date period
	 *
	 * @param $period The date period
	 */
	public function findAllByDatePeriod($period) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE s.date BETWEEN :begin AND :end')
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getResult();

		//Send result
		return $ret;
	}

	/**
	 * Find sessions by location and date period
	 *
	 * @param $location The location
	 * @param $period The date period
	 */
	public function findAllByLocationDatePeriod($location, $period) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Session s WHERE (s.location = :location AND s.date BETWEEN :begin AND :end)')
			->setParameter('location', $location)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getResult();

		//Send result
		return $ret;
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
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp)
		];

		//Set the request
		$req = 'SELECT s.id, s.date, s.location_id AS l_id, l.title AS l_title, s.slot_id AS t_id, t.title AS t_title, s.application_id AS a_id, a.user_id AS a_u_id, au.pseudonym AS a_u_pseudonym, GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\n") AS as_u_id, GROUP_CONCAT(sau.pseudonym ORDER BY sa.user_id SEPARATOR "\n") AS as_u_pseudonym
			FROM RapsysAirBundle:Session AS s
			JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
			JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
			'.($granted?'':'LEFT ').'JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
			'.($granted?'':'LEFT ').'JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
			LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
			LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
			WHERE s.date BETWEEN :begin AND :end
			'.($locationId?'AND s.location_id = :lid':'').'
			GROUP BY s.id
			ORDER BY NULL';

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
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('a_u_id', 'a_u_id', 'integer')
			->addScalarResult('a_u_pseudonym', 'a_u_pseudonym', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('as_u_id', 'as_u_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('as_u_pseudonym', 'as_u_pseudonym', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $em
			->createNativeQuery($req, $rsm)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->setParameter('lid', $locationId)
			->getResult();

		//Init calendar
		$calendar = [];

		//Init month
		$month = null;

		//Iterate on each day
		foreach($period as $date) {
			//Init day in calendar
			$calendar[$Ymd = $date->format('Ymd')] = [
				'title' => $date->format('d'),
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
				$calendar[$Ymd]['class'][] =  'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] =  'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				$calendar[$Ymd]['class'][] =  'next';
			}

			//Iterate on each session to find the one of the day
			foreach($res as $session) {
				if (($sessionYmd = $session['date']->format('Ymd')) == $Ymd) {
					//Count number of application
					$count = count(explode("\n", $session['as_u_id']));

					//Compute classes
					$class = [];
					if (!empty($session['a_id'])) {
						$applications = [ $session['a_u_id'] => $session['a_u_pseudonym'] ];
						$class[] = 'granted';
					} elseif ($count == 0) {
						$class[] = 'orphaned';
					} elseif ($count > 1) {
						$class[] = 'disputed';
					} else {
						$class[] = 'pending';
					}

					if ($sessionId == $session['id']) {
						$class[] = 'highlight';
					}

					//Check that session is not granted
					if (empty($session['a_id'])) {
						//Fetch pseudonyms from session applications
						$applications = array_combine(explode("\n", $session['as_u_id']), explode("\n", $session['as_u_pseudonym']));
					}

					//Add the session
					//XXX: see if we shouldn't prepend with 0 the slot and location to avoid collision ???
					$calendar[$Ymd]['sessions'][$session['t_id'].$session['l_id']] = [
						'id' => $session['id'],
						'title' => $translator->trans($session['l_title']).' ('.$translator->trans($session['t_title']).')',
						'class' => $class,
						'applications' => [ 0 => $translator->trans($session['t_title']).' '.$translator->trans('at '.$session['l_title']).($count > 1?' ['.$count.']':'') ]+$applications
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
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp)
		];

		//Set the request
		$req = 'SELECT s.id, s.date, s.location_id AS l_id, l.title AS l_title, s.slot_id AS t_id, t.title AS t_title, s.application_id AS a_id, a.user_id AS a_u_id, au.pseudonym AS a_u_pseudonym, GROUP_CONCAT(sa.user_id ORDER BY sa.user_id SEPARATOR "\n") AS as_u_id, GROUP_CONCAT(sau.pseudonym ORDER BY sa.user_id SEPARATOR "\n") AS as_u_pseudonym
			FROM RapsysAirBundle:Session AS s
			JOIN RapsysAirBundle:Location AS l ON (l.id = s.location_id)
			JOIN RapsysAirBundle:Slot AS t ON (t.id = s.slot_id)
			LEFT JOIN RapsysAirBundle:Application AS a ON (a.id = s.application_id)
			LEFT JOIN RapsysAirBundle:User AS au ON (au.id = a.user_id)
			LEFT JOIN RapsysAirBundle:Application AS sa ON (sa.session_id = s.id)
			LEFT JOIN RapsysAirBundle:User AS sau ON (sau.id = sa.user_id)
			WHERE s.date BETWEEN :begin AND :end
			'.($userId?' AND sa.user_id = :uid':'').'
			GROUP BY s.id
			ORDER BY NULL';

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
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('l_id', 'l_id', 'integer')
			->addScalarResult('l_title', 'l_title', 'string')
			->addScalarResult('a_id', 'a_id', 'integer')
			->addScalarResult('a_u_id', 'a_u_id', 'integer')
			->addScalarResult('a_u_pseudonym', 'a_u_pseudonym', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('as_u_id', 'as_u_id', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('as_u_pseudonym', 'as_u_pseudonym', 'string')
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
				'title' => $date->format('d'),
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
				$calendar[$Ymd]['class'][] =  'current';
			}
			//Disable passed days
			if ($date->format('U') < $today) {
				$calendar[$Ymd]['disabled'] = true;
				$calendar[$Ymd]['class'][] =  'disabled';
			}
			//Set next month days
			if ($date->format('m') > date('m')) {
				$calendar[$Ymd]['next'] = true;
				$calendar[$Ymd]['class'][] =  'next';
			}

			//Iterate on each session to find the one of the day
			foreach($res as $session) {
				if (($sessionYmd = $session['date']->format('Ymd')) == $Ymd) {
					//Count number of application
					$count = count(explode("\n", $session['as_u_id']));

					//Compute classes
					$class = [];
					if (!empty($session['a_id'])) {
						$applications = [ $session['a_u_id'] => $session['a_u_pseudonym'] ];
						if ($session['a_u_id'] == $userId) {
							$class[] = 'granted';
						} else {
							$class[] = 'disputed';
						}
					} elseif ($count == 0) {
						$class[] = 'orphaned';
					} elseif ($count > 1) {
						$class[] = 'disputed';
					} else {
						$class[] = 'pending';
					}

					if ($sessionId == $session['id']) {
						$class[] = 'highlight';
					}

					//Check that session is not granted
					if (empty($session['a_id'])) {
						//Fetch pseudonyms from session applications
						$applications = array_combine(explode("\n", $session['as_u_id']), explode("\n", $session['as_u_pseudonym']));
					}

					//Add the session
					//XXX: see if we shouldn't prepend with 0 the slot and location to avoid collision ???
					$calendar[$Ymd]['sessions'][$session['t_id'].$session['l_id']] = [
						'id' => $session['id'],
						'title' => $translator->trans($session['l_title']).' ('.$translator->trans($session['t_title']).')',
						'class' => $class,
						'applications' => [ 0 => $translator->trans($session['t_title']).' '.$translator->trans('at '.$session['l_title']).($count > 1?' ['.$count.']':'') ]+$applications
					];
				}
			}

			//Sort sessions
			ksort($calendar[$Ymd]['sessions']);
		}

		//Send result
		return $calendar;
	}
}
