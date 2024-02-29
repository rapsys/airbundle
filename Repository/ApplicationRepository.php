<?php

namespace Rapsys\AirBundle\Repository;

/**
 * ApplicationRepository
 */
class ApplicationRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find session by session and user
	 *
	 * @param $session The session
	 * @param $user The user
	 */
	public function findOneBySessionUser($session, $user) {
		//Fetch article
		$ret = $this->getEntityManager()
			->createQuery('SELECT a FROM Rapsys\AirBundle\Entity\Application a WHERE (a.session = :session AND a.user = :user)')
			->setParameter('session', $session)
			->setParameter('user', $user)
			->getSingleResult();

		//Send result
		return $ret;
	}
}
