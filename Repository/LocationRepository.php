<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * LocationRepository
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository {
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
}
