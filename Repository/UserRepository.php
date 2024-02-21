<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * UserRepository
 */
class UserRepository extends Repository {
	/**
	 * Find users with translated highest group and civility
	 *
	 * @return array The user ids keyed by group and pseudonym
	 */
	public function findChoicesAsArray(): array {
		//Set the request
		$req =<<<SQL
SELECT
	a.id,
	a.pseudonym,
	a.g_id,
	a.g_title
FROM (
	SELECT
		u.id,
		u.pseudonym,
		g.id AS g_id,
		g.title AS g_title
	FROM RapsysAirBundle:User AS u
	JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
	JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
	WHERE g.title <> 'User'
	ORDER BY g.id DESC, u.pseudonym ASC
	LIMIT 0, :limit
) AS a
GROUP BY a.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

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
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Without group or simple user
			#XXX: moved in sql by removing LEFT JOIN and excluding user group
			#if (empty($data['g_title']) || $data['g_title'] == 'User') {
			#	//Skip it
			#	continue;
			#}

			//Get translated group
			$group = $this->translator->trans($data['g_title']);

			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}

			//Set data
			//XXX: ChoiceType use display string as key
			$ret[$group][trim($data['pseudonym'].' ('.$data['id'].')')] = intval($data['id']);
		}

		//Send result
		return $ret;
	}

	/**
	 * Find user ids by pseudonym
	 *
	 * @param array $pseudonym The pseudonym filter
	 * @return array The user ids
	 */
	public function findIdByPseudonymAsArray(array $pseudonym): array {
		//Set the request
		$req =<<<SQL
SELECT
	a.id
FROM (
	SELECT
		u.id
	FROM RapsysAirBundle:User AS u
	LEFT JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
	WHERE u.pseudonym IN (:pseudonym)
	ORDER BY gu.group_id DESC, u.pseudonym ASC
	LIMIT 0, :limit
) AS a
GROUP BY a.id
ORDER BY NULL
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//XXX: we don't use a result set as we want to translate group and civility
		$rsm->addScalarResult('id', 'id', 'integer');

		//Return result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('pseudonym', $pseudonym)
			//XXX: instead of array_column on the result
			->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
	}

	/**
	 * Find applicant by session id
	 *
	 * @param int $sessionId The Session id
	 * @return array The pseudonym array keyed by id
	 */
	public function findBySessionId(int $sessionId): array {
		//Set the request
		$req =<<<SQL
SELECT u.id, u.pseudonym
FROM RapsysAirBundle:Application AS a
JOIN RapsysAirBundle:User AS u ON (u.id = a.user_id)
WHERE a.session_id = :id
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//XXX: we don't use a result set as we want to translate group and civility
		$rsm->addScalarResult('id', 'id', 'integer')
			->addIndexByScalar('pseudonym');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('id', $sessionId)
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each result
		foreach($result as $id => $data) {
			//Add to return
			$return[$id] = $data['id'];
		}

		//Return return
		return $return;
	}

	/**
	 * Find user as array by id
	 *
	 * @param int $id The location id
	 * @param string $locale The locale
	 * @return array The location data
	 */
	public function findOneByIdAsArray(int $id, string $locale): ?array {
		//Set the request
		//TODO: zipcode/city/country (on pourra matcher les locations avec ça ?)
		$req =<<<SQL
SELECT
	u.id,
	u.city,
	u.forename,
	u.mail,
	u.phone,
	u.pseudonym,
	u.surname,
	u.updated,
	u.zipcode,
	u.civility_id AS c_id,
	c.title AS c_title,
	u.country_id AS o_id,
	o.title AS o_title,
	GROUP_CONCAT(g.id ORDER BY g.id SEPARATOR "\\n") AS ids,
	GROUP_CONCAT(g.title ORDER BY g.id SEPARATOR "\\n") AS titles,
	GREATEST(COALESCE(u.updated, 0), COALESCE(c.updated, 0), COALESCE(o.updated, 0)) AS modified
FROM RapsysAirBundle:User AS u
LEFT JOIN RapsysAirBundle:Civility AS c ON (c.id = u.civility_id)
LEFT JOIN RapsysAirBundle:Country AS o ON (o.id = u.country_id)
LEFT JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
LEFT JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
WHERE u.id = :id
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('city', 'city', 'string')
			->addScalarResult('forename', 'forename', 'string')
			->addScalarResult('mail', 'mail', 'string')
			->addScalarResult('phone', 'phone', 'string')
			->addScalarResult('pseudonym', 'pseudonym', 'string')
			->addScalarResult('surname', 'surname', 'string')
			->addScalarResult('updated', 'updated', 'datetime')
			->addScalarResult('zipcode', 'zipcode', 'string')
			->addScalarResult('c_id', 'c_id', 'integer')
			->addScalarResult('c_title', 'c_title', 'string')
			->addScalarResult('o_id', 'o_id', 'integer')
			->addScalarResult('o_title', 'o_title', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('ids', 'ids', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('titles', 'titles', 'string')
			->addScalarResult('modified', 'modified', 'datetime')
			->addIndexByScalar('id');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('id', $id)
			->getOneOrNullResult();

		//Without result
		if ($result === null) {
			//Return result
			return $result;
		}

		//Set alternates
		$result['alternates'] = [];

		//Set route
		$route = 'rapsys_air_user_view';

		//Set route params
		$routeParams = ['id' => $id, 'user' => $this->slugger->slug($result['pseudonym'])];

		//Milonga Raphaël exception
		if ($routeParams['id'] == 1 && $routeParams['user'] == 'milonga-raphael') {
			//Set route
			$route = 'rapsys_air_user_milongaraphael';
			//Set route params
			$routeParams = [];
		}

		//Iterate on each languages
		foreach($this->languages as $languageId => $language) {
			//Without current locale
			if ($languageId !== $locale) {
				//Set titles
				$titles = [];

				//Set route params locale
				$routeParams['_locale'] = $languageId;

				//Iterate on each locales
				foreach(array_keys($this->languages) as $other) {
					//Without other locale
					if ($other !== $languageId) {
						//Set other locale title
						$titles[$other] = $this->translator->trans($language, [], null, $other);
					}
				}

				//Add alternates locale
				$result['alternates'][substr($languageId, 0, 2)] = $result['alternates'][str_replace('_', '-', $languageId)] = [
					'absolute' => $this->router->generate($route, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($route, $routeParams),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($language, [], null, $languageId)
				];
			}
		}

		//Set titles
		$titles = explode("\n", $result['titles']);

		//Set groups and roles
		$groups = $roles = [];

		//Iterate on each location
		foreach(explode("\n", $result['ids']) as $k => $id) {
			//Add role
			$roles[$role = 'ROLE_'.strtoupper($titles[$k])] = $role;

			//Add group
			$groups[$id] = $this->translator->trans($titles[$k]);
		}

		//Return result
		return [
			'id' => $result['id'],
			'mail' => $result['mail'],
			'pseudonym' => $result['pseudonym'],
			'forename' => $result['forename'],
			'surname' => $result['surname'],
			'phone' => $result['phone'],
			'zipcode' => $result['zipcode'],
			'city' => $result['city'],
			'civility' => [
				'id' => $result['c_id'],
				'title' => $this->translator->trans($result['c_title'])
			],
			'country' => [
				'id' => $result['o_id'],
				//XXX: without country, o_title is empty
				'title' => $this->translator->trans($result['o_title'])
			],
			'updated' => $result['updated'],
			'roles' => $roles,
			'groups' => $groups,
			'modified' => $result['modified'],
			'multimap' => $this->translator->trans('%pseudonym% sector map', ['%pseudonym%' => $result['pseudonym']]),
			'slug' => $this->slugger->slug($result['pseudonym']),
			'link' => $this->router->generate($route, ['_locale' => $locale]+$routeParams),
			'alternates' => $result['alternates']
		];
	}

	/**
	 * Find all users grouped by translated group
	 *
	 * @return array The user mail and pseudonym keyed by group and id
	 */
	public function findIndexByGroupId(): array {
		//Set the request
		$req = <<<SQL
SELECT
	t.id,
	t.mail,
	t.forename,
	t.surname,
	t.pseudonym,
	t.g_id,
	t.g_title,
	GROUP_CONCAT(t.d_id ORDER BY t.d_id SEPARATOR "\\n") AS d_ids,
	GROUP_CONCAT(t.d_name ORDER BY t.d_id SEPARATOR "\\n") AS d_names,
	GROUP_CONCAT(t.d_type ORDER BY t.d_id SEPARATOR "\\n") AS d_types
FROM (
	SELECT
		c.id,
		c.mail,
		c.forename,
		c.surname,
		c.pseudonym,
		c.g_id,
		c.g_title,
		d.id AS d_id,
		d.name AS d_name,
		d.type AS d_type
	FROM (
		SELECT
			u.id,
			u.mail,
			u.forename,
			u.surname,
			u.pseudonym,
			g.id AS g_id,
			g.title AS g_title
		FROM RapsysAirBundle:User AS u
		JOIN RapsysAirBundle:UserGroup AS gu ON (gu.user_id = u.id)
		JOIN RapsysAirBundle:Group AS g ON (g.id = gu.group_id)
		ORDER BY NULL
		LIMIT 0, :limit
	) AS c
	LEFT JOIN RapsysAirBundle:Application AS a ON (a.user_id = c.id)
	LEFT JOIN RapsysAirBundle:Dance AS d ON (d.id = a.dance_id)
	GROUP BY d.id
	ORDER BY NULL
	LIMIT 0, :limit
) AS t
GROUP BY t.g_id, t.id
ORDER BY t.g_id DESC, t.id ASC
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('id', 'id', 'integer')
			->addScalarResult('mail', 'mail', 'string')
			->addScalarResult('forename', 'forename', 'string')
			->addScalarResult('surname', 'surname', 'string')
			->addScalarResult('pseudonym', 'pseudonym', 'string')
			->addScalarResult('g_id', 'g_id', 'integer')
			->addScalarResult('g_title', 'g_title', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('d_ids', 'd_ids', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('d_names', 'd_names', 'string')
			//XXX: is a string because of \n separator
			->addScalarResult('d_types', 'd_types', 'string');

		//Fetch result
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Get translated group
			$group = $this->translator->trans($data['g_title']);

			//Init group subarray
			if (!isset($ret[$group])) {
				$ret[$group] = [];
			}

			//Set dances
			$dances = [];

			//Set data
			$ret[$group][$data['id']] = [
				'mail' => $data['mail'],
				'forename' => $data['forename'],
				'surname' => $data['surname'],
				'pseudonym' => $data['pseudonym'],
				'dances' => [],
				'slug' => $slug = $this->slugger->slug($data['pseudonym']),
				//Milonga Raphaël exception
				'link' => $data['id'] == 1 && $slug == 'milonga-raphael' ? $this->router->generate('rapsys_air_user_milongaraphael', []) : $this->router->generate('rapsys_air_user_view', ['id' => $data['id'], 'user' => $slug]),
				'edit' => $this->router->generate('rapsys_user_edit', ['mail' => $short = $this->slugger->short($data['mail']), 'hash' => $this->slugger->hash($short)])
			];

			//With dances
			if (!empty($data['d_ids'])) {
				//Set names
				$names = explode("\n", $data['d_names']);

				//Set types
				$types = explode("\n", $data['d_types']);

				//Iterate on each dance
				foreach(explode("\n", $data['d_ids']) as $k => $id) {
					//Init dance when missing
					if (!isset($ret[$group][$data['id']]['dances'][$name = $this->translator->trans($names[$k])])) {
						$ret[$group][$data['id']]['dances'][$name] = [
							'link' => $this->router->generate('rapsys_air_dance_name', ['name' => $this->slugger->short($names[$k]), 'dance' => $this->slugger->slug($name)]),
							'types' => []
						];
					}

					//Set type
					$ret[$group][$data['id']]['dances'][$name]['types'][$type = $this->translator->trans($types[$k])] = $this->router->generate('rapsys_air_dance_view', ['id' => $id, 'name' => $this->slugger->slug($name), 'type' => $this->slugger->slug($type)]);
				}
			}
		}

		//Send result
		return $ret;
	}
}
