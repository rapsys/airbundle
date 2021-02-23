<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * UserRepository
 */
class UserRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find users with translated highest group and civility
	 *
	 * @param $translator The TranslatorInterface instance
	 */
	public function findAllWithTranslatedGroupAndCivility(TranslatorInterface $translator) {
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
			'RapsysAirBundle:Civility' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Civility'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp)
		];

		//Set the request
		$req = 'SELECT a.id, a.forename, a.surname, a.t_id, a.t_short, a.t_title, a.g_id, a.g_title FROM (
			SELECT u.id, u.forename, u.surname, t.id AS t_id, t.short AS t_short, t.title AS t_title, g.id AS g_id, g.title AS g_title
			FROM RapsysAirBundle:User AS u
			JOIN RapsysAirBundle:Civility AS t ON (t.id = u.civility_id)
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

	/**
	 * Find all applicant by session
	 *
	 * @param $session The Session
	 */
	public function findAllApplicantBySession($session) {
		//Get entity manager
		$em = $this->getEntityManager();

		//Fetch sessions
		$ret = $this->getEntityManager()
			->createQuery('SELECT u.id, u.pseudonym FROM RapsysAirBundle:Application a JOIN RapsysAirBundle:User u WITH u.id = a.user WHERE a.session = :session')
			->setParameter('session', $session)
			->getResult();

		//Process result
		$ret = array_column($ret, 'id', 'pseudonym');

		//Send result
		return $ret;
	}

	/**
	 * Find all users grouped by translated group
	 *
	 * @param $translator The TranslatorInterface instance
	 * @return array|null The user array or null
	 */
	public function findUserGroupedByTranslatedGroup(TranslatorInterface $translator) {
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
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp),
			//XXX: Set limit used to workaround mariadb subselect optimization
			':limit' => PHP_INT_MAX,
			"\t" => '',
			"\n" => ' '
		];

		//Set the request
		$req = <<<SQL
SELECT a.id, a.pseudonym, a.g_id, a.g_title
FROM (
	SELECT u.id, u.pseudonym, g.id AS g_id, g.title AS g_title
	FROM RapsysAirBundle:User AS u
	JOIN RapsysAirBundle:GroupUser AS gu ON (gu.user_id = u.id)
	JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
	ORDER BY g.id DESC
	LIMIT 0, :limit
) AS a
GROUP BY a.id
ORDER BY a.id ASC
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
			->addScalarResult('pseudonym', 'pseudonym', 'string')
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

			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}

			//Set data
			$ret[$group][$data['id']] = $data['pseudonym'];
		}

		//Send result
		return $ret;
	}
}
