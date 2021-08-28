<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * DanceRepository
 */
class DanceRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find dances by user id
	 *
	 * @param $id The user id
	 * @return array The user dances
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
			'RapsysAirBundle:UserDance' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('dances'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Dance' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Dance'), $dp)
		];

		//Set the request
		$req = 'SELECT d.id, d.title
FROM RapsysAirBundle:UserDance AS ud
JOIN RapsysAirBundle:Dance AS d ON (d.id = ud.dance_id)
WHERE ud.user_id = :uid';

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare result set for our request
		$rsm->addEntityResult('RapsysAirBundle:Dance', 'd');
		$rsm->addFieldResult('d', 'id', 'id');
		$rsm->addFieldResult('d', 'title', 'title');

		//Send result
		return $em
			->createNativeQuery($req, $rsm)
			->setParameter('uid', $userId)
			->getResult();
	}
}
