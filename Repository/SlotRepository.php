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

use Rapsys\AirBundle\Repository;

/**
 * SlotRepository
 */
class SlotRepository extends Repository {
	/**
	 * Find slots with translated title
	 *
	 * @return array The slots id keyed by translated title
	 */
	public function findAllWithTranslatedTitle(): array {
		//Set the request from quoted table name
		//XXX: this allow to make this code table name independent
		$req = 'SELECT s.id, s.title FROM Rapsys\AirBundle\Entity\Slot AS s';

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

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
		$res = $this->_em
			->createNativeQuery($req, $rsm)
			->getResult();

		//Init return
		$ret = [];

		//Process result
		foreach($res as $data) {
			//Get translated slot
			$slot = $this->translator->trans($data['title']);
			//Set data
			//XXX: ChoiceType use display string as key
			$ret[$slot] = $data['id'];
		}

		//Send result
		return $ret;
	}
}
