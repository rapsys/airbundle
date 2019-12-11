<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * SlotRepository
 */
class SlotRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find slots with translated title
	 *
	 * @param $translator The TranslatorInterface instance
	 */
	public function findAllWithTranslatedTitle(TranslatorInterface $translator) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Set the request from quoted table name
		//XXX: this allow to make this code table name independent
		$req = 'SELECT s.id, s.title FROM '.$qs->getTableName($em->getClassMetadata('RapsysAirBundle:Slot'), $dp).' AS s';

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('title', 'title', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Get translated slot
			$slot = $translator->trans($data['title']);
			//Set data
			//XXX: ChoiceType use display string as key
			$ret[$slot] = $data['id'];
		}

		//Send result
		return $ret;
	}
}
