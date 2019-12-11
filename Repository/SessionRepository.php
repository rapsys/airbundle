<?php

namespace Rapsys\AirBundle\Repository;

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
}
