<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;

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
	 * Fetch translated location with session by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $granted The session is granted
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
	 * Fetch translated user location with session by date period
	 *
	 * @param $translator The TranslatorInterface instance
	 * @param $period The date period
	 * @param $userId The user uid
	 */
	public function fetchTranslatedUserLocationByDatePeriod(TranslatorInterface $translator, $period, $userId) {
		//Fetch sessions
		$ret = $this->getEntityManager()
			  ->createQuery('SELECT l.id, l.title FROM RapsysAirBundle:Application a JOIN RapsysAirBundle:Session s JOIN RapsysAirBundle:Location l WHERE a.user = :uid AND a.session = s.id AND s.date BETWEEN :begin AND :end AND s.location = l.id GROUP BY l.id ORDER BY l.id')
#SELECT l.id, l.title FROM RapsysAirBundle:Session s JOIN RapsysAirBundle:Application a JOIN RapsysAirBundle:Location l WHERE s.date BETWEEN :begin AND :end AND s.id = a.session AND l.id = s.location GROUP BY l.id ORDER BY l.id
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
}
