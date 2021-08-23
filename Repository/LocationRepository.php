<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * LocationRepository
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find complementary locations by session id
	 *
	 * @param $id The session id
	 * @return array The other locations
	 */
	public function findComplementBySessionId($id) {
		//Fetch complement locations
		$ret = $this->getEntityManager()
			  ->createQuery('SELECT l.id, l.title FROM RapsysAirBundle:Session s LEFT JOIN RapsysAirBundle:Session s2 WITH s2.id != s.id AND s2.slot = s.slot AND s2.date = s.date LEFT JOIN RapsysAirBundle:Location l WITH l.id != s.location AND (l.id != s2.location OR s2.location IS NULL) WHERE s.id = :sid GROUP BY l.id ORDER BY l.id')
			->setParameter('sid', $id)
			->getArrayResult();

		//Rekey array
		$ret = array_column($ret, 'id', 'title');

		return $ret;
	}

	/**
	 * Find translated location title sorted by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $granted The session is granted
	 */
	public function findTranslatedSortedByPeriod(TranslatorInterface $translator, $period, $userId = null) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery(
'SELECT l.id, l.title
FROM RapsysAirBundle:Location l
LEFT JOIN RapsysAirBundle:Session s WITH s.location = l.id AND s.date BETWEEN :begin AND :end
LEFT JOIN RapsysAirBundle:Application a WITH a.id = s.application'.(!empty($userId)?' AND a.user = :uid':'').'
GROUP BY l.id
ORDER BY '.(!empty($userId)?'COUNT(a.id) DESC, ':'').'COUNT(s.id) DESC, l.id'
			)
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate());

		//Set optional user id
		if (!empty($userId)) {
			$ret->setParameter('uid', $userId);
		}

		//Get Result
		$ret = $ret->getResult();

		//Rekey array
		$ret = array_column($ret, 'title', 'id');

		//Filter array
		foreach($ret as $k => $v) {
			$ret[$k] = $translator->trans($v);
		}

		//Send result
		return $ret;
	}

	/**
	 * Fetch translated location title with session by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $granted The session is granted
	 * TODO: a dropper
	 */
	public function fetchTranslatedLocationByDatePeriod(TranslatorInterface $translator, $period, $granted = false) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery('SELECT l.id, l.title FROM RapsysAirBundle:Session s JOIN RapsysAirBundle:Location l WHERE '.($granted?'s.application IS NOT NULL AND ':'').'l.id = s.location AND s.date BETWEEN :begin AND :end GROUP BY l.id ORDER BY l.id')
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->getResult();

		//Rekey array
		$ret = array_column($ret, 'title', 'id');

		//Filter array
		foreach($ret as $k => $v) {
			$ret[$k] = $translator->trans($v);
		}

		//Send result
		return $ret;
	}

	/**
	 * Fetch translated location title with user session by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $userId The user uid
	 * TODO: a dropper
	 */
	public function fetchTranslatedUserLocationByDatePeriod(TranslatorInterface $translator, $period, $userId) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery('SELECT l.id, l.title FROM RapsysAirBundle:Application a JOIN RapsysAirBundle:Session s JOIN RapsysAirBundle:Location l WHERE a.user = :uid AND a.session = s.id AND s.date BETWEEN :begin AND :end AND s.location = l.id GROUP BY l.id ORDER BY l.id')
			->setParameter('begin', $period->getStartDate())
			->setParameter('end', $period->getEndDate())
			->setParameter('uid', $userId)
			->getResult();

		//Rekey array
		$ret = array_column($ret, 'title', 'id');

		//Filter array
		foreach($ret as $k => $v) {
			$ret[$k] = $translator->trans($v);
		}

		//Send result
		return $ret;
	}

	/**
	 * Find locations by user id
	 *
	 * @param $id The user id
	 * @return array The user locations
	 */
	public function findByUserId($userId) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:UserLocation' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('locations'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Location'), $dp)
		];

		//Set the request
		$req = 'SELECT l.id, l.title, l.short
FROM RapsysAirBundle:UserLocation AS ul
JOIN RapsysAirBundle:Location AS l ON (l.id = ul.location_id)
WHERE ul.user_id = :uid';

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare result set for our request
		$rsm->addEntityResult('RapsysAirBundle:Location', 'l');
		$rsm->addFieldResult('l', 'id', 'id');
		$rsm->addFieldResult('l', 'title', 'title');
		$rsm->addFieldResult('l', 'short', 'short');

		//Send result
		return $em
			->createNativeQuery($req, $rsm)
			->setParameter('uid', $userId)
			->getResult();
	}
}
