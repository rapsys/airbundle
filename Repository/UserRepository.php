<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * UserRepository
 */
class UserRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find users with translated highest group and title
	 *
	 * @param $translator The TranslatorInterface instance
	 */
	public function findAllWithTranslatedGroupAndTitle(TranslatorInterface $translator) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Get quote strategy
		$qs = $em->getConfiguration()->getQuoteStrategy();
		$dp = $em->getConnection()->getDatabasePlatform();

		//Get quoted table names
		//XXX: this allow to make this code table name independent
		$tables = [
			'RapsysAirBundle:GroupUser' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:Title' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Title'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp)
		];

		//Set the request
		$req = 'SELECT a.id, a.forename, a.surname, a.t_id, a.t_short, a.t_title, a.g_id, a.g_title FROM (
			SELECT u.id, u.forename, u.surname, t.id AS t_id, t.short AS t_short, t.title AS t_title, g.id AS g_id, g.title AS g_title
			FROM RapsysAirBundle:User AS u
			JOIN RapsysAirBundle:Title AS t ON (t.id = u.title_id)
			LEFT JOIN RapsysAirBundle:GroupUser AS gu ON (gu.user_id = u.id)
			LEFT JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
			ORDER BY g.id DESC, NULL LIMIT '.PHP_INT_MAX.'
		) AS a GROUP BY a.id ORDER BY NULL';

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		/*XXX: we don't want a result set for our request
		$rsm->addEntityResult('RapsysAirBundle:User', 'u');
		$rsm->addFieldResult('u', 'id', 'id');
		$rsm->addFieldResult('u', 'forename', 'forename');
		$rsm->addFieldResult('u', 'surname', 'surname');
		$rsm->addFieldResult('u', 't_id', 'title_id');
		$rsm->addJoinedEntityResult('RapsysAirBundle:Title', 't', 'u', 'title');
		$rsm->addFieldResult('t', 't_id', 'id');
		$rsm->addFieldResult('t', 't_title', 'title');
		$rsm->addJoinedEntityResult('RapsysAirBundle:Group', 'g', 'u', 'groups');
		$rsm->addFieldResult('g', 'g_id', 'id');
		$rsm->addFieldResult('g', 'g_title', 'title');*/

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('forename', 'forename', 'string')
			->addScalarResult('surname', 'surname', 'string')
			->addScalarResult('t_id', 't_id', 'integer')
			->addScalarResult('t_short', 't_short', 'string')
			->addScalarResult('t_title', 't_title', 'string')
			->addScalarResult('g_id', 'g_id', 'integer')
			->addScalarResult('g_title', 'g_title', 'string')
			->addIndexByScalar('id');

		//Fetch result
		$res = $em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Get translated group
			$group = $translator->trans($data['g_title']?:'User');
			//Get translated title
			$title = $translator->trans($data['t_short']);
			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}
			//Set data
			//XXX: ChoiceType use display string as key
			$ret[$group][$title.' '.$data['forename'].' '.$data['surname']] = $data['id'];
		}

		//Send result
		return $ret;
	}
}