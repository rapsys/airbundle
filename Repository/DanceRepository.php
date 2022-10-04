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

use Doctrine\ORM\Query\ResultSetMapping;

/**
 * DanceRepository
 */
class DanceRepository extends EntityRepository {
	/**
	 * Find dance names as array
	 *
	 * @return array The dance names
	 */
	public function findNamesAsArray() {
		//Set the request
		$req = <<<SQL
SELECT
	d.name,
	GROUP_CONCAT(d.id ORDER BY d.id SEPARATOR "\\n") AS ids,
	GROUP_CONCAT(d.type ORDER BY d.id SEPARATOR "\\n") AS types,
	MAX(d.updated) AS modified
FROM RapsysAirBundle:Dance AS d
GROUP BY d.name
ORDER BY d.name
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm->addScalarResult('name', 'name', 'string')
			->addScalarResult('ids', 'ids', 'string')
			->addScalarResult('types', 'types', 'string')
			->addScalarResult('modified', 'modified', 'datetime')
			->addIndexByScalar('name');

		//Get result
		$result = $this->_em
			->createNativeQuery($req, $rsm)
			->getArrayResult();

		//Set return
		$return = [];

		//Iterate on each name
		foreach($result as $name) {
			//Set name slug
			$slug = $this->slugger->slug($tname = $this->translator->trans($name['name']));

			//Set types
			$types = [];

			//Explode ids
			$name['ids'] = explode("\n", $name['ids']);

			//Explode types
			$name['types'] = explode("\n", $name['types']);

			//Iterate on each type
			foreach($name['ids'] as $k => $id) {
				//Add to types
				$types[$this->slugger->short($name['types'][$k])] = [
					'id' => $id,
					'type' => $type = $this->translator->trans($name['types'][$k]),
					'slug' => $stype = $this->slugger->slug($type),
					'link' => $this->router->generate('rapsys_air_dance_view', ['id' => $id, 'name' => $slug, 'type' => $stype])
				];
			}

			//Add to return
			$return[$sname = $this->slugger->short($name['name'])] = [
				'name' => $tname,
				'slug' => $slug,
				'link' => $this->router->generate('rapsys_air_dance_name', ['name' => $sname, 'dance' => $slug]),
				'types' => $types,
				'modified' => $name['modified']
			];
		}

		//Return return
		return $return;
	}

	/**
	 * Find dances by user id
	 *
	 * @param $id The user id
	 * @return array The user dances
	 */
	public function findByUserId($userId) {
		//Set the request
		$req = 'SELECT d.id, d.name, d.type
FROM RapsysAirBundle:UserDance AS ud
JOIN RapsysAirBundle:Dance AS d ON (d.id = ud.dance_id)
WHERE ud.user_id = :uid';

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare result set for our request
		$rsm->addEntityResult('RapsysAirBundle:Dance', 'd');
		$rsm->addFieldResult('d', 'id', 'id');
		$rsm->addFieldResult('d', 'name', 'name');
		$rsm->addFieldResult('d', 'type', 'type');

		//Send result
		return $this->_em
			->createNativeQuery($req, $rsm)
			->setParameter('uid', $userId)
			->getResult();
	}
}
