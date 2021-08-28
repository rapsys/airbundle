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
			'RapsysAirBundle:UserGroup' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp)
		];

		//Set the request
		$req = 'SELECT a.id, a.pseudonym, a.g_id, a.g_title FROM (
			SELECT u.id, u.pseudonym, g.id AS g_id, g.title AS g_title
			FROM RapsysAirBundle:User AS u
			LEFT JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
			LEFT JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
			ORDER BY g.id DESC, NULL LIMIT '.PHP_INT_MAX.'
		) AS a GROUP BY a.id ORDER BY NULL';

		//Replace bundle entity name by table name
		$req = str_replace(array_keys($tables), array_values($tables), $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//XXX: we don't use a result set as we want to translate group and civility
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
			//Without group or simple user
			if (empty($data['g_title']) || $data['g_title'] == 'User') {
				//Skip it
				continue;
			}
			//Get translated group
			$group = $translator->trans($data['g_title']);
			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}
			//Set data
			//XXX: ChoiceType use display string as key
			$ret[$group][$data['pseudonym']] = $data['id'];
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
			'RapsysAirBundle:UserGroup' => $qs->getJoinTableName($em->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $em->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($em->getClassMetadata('RapsysAirBundle:User'), $dp),
			//XXX: Set limit used to workaround mariadb subselect optimization
			':limit' => PHP_INT_MAX,
			"\t" => '',
			"\n" => ' '
		];

		//Set the request
		$req = <<<SQL
SELECT u.id, u.mail, u.pseudonym, g.id AS g_id, g.title AS g_title
FROM RapsysAirBundle:User AS u
JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
ORDER BY g.id DESC, u.id ASC
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
			->addScalarResult('mail', 'mail', 'string')
			->addScalarResult('pseudonym', 'pseudonym', 'string')
			->addScalarResult('g_id', 'g_id', 'integer')
			->addScalarResult('g_title', 'g_title', 'string');

		//Fetch result
		$res = $em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Get translated group
			$group = $translator->trans($data['g_title']);

			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}

			//Set data
			$ret[$group][$data['id']] = [
				'mail' => $data['mail'],
				'pseudonym' => $data['pseudonym']
			];
		}

		//Send result
		return $ret;
	}
}
