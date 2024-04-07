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

use Rapsys\AirBundle\Repository;

/**
 * GoogleTokenRepository
 */
class GoogleTokenRepository extends Repository {
	/**
	 * Find google tokens indexed by id
	 *
	 * @return array The google tokens array
	 */
	public function findAllIndexed(): array {
		//Set the request
		$req = <<<SQL
SELECT
	b.tid,
	b.gmail,
	b.uid,
	b.access,
	b.refresh,
	b.expired,
	b.cids,
	b.cmails,
	b.csummaries,
	b.csynchronizeds,
	b.dids,
	GROUP_CONCAT(us.subscribed_id ORDER BY us.subscribed_id SEPARATOR "\\n") AS sids
FROM (
	SELECT
		a.tid,
		a.gmail,
		a.uid,
		a.access,
		a.refresh,
		a.expired,
		a.cids,
		a.cmails,
		a.csummaries,
		a.csynchronizeds,
		GROUP_CONCAT(ud.dance_id ORDER BY ud.dance_id SEPARATOR "\\n") AS dids
	FROM (
		SELECT
			t.id AS tid,
			t.mail AS gmail,
			t.user_id AS uid,
			t.access,
			t.refresh,
			t.expired,
			GROUP_CONCAT(c.id ORDER BY c.id SEPARATOR "\\n") AS cids,
			GROUP_CONCAT(c.mail ORDER BY c.id SEPARATOR "\\n") AS cmails,
			GROUP_CONCAT(c.summary ORDER BY c.id SEPARATOR "\\n") AS csummaries,
			GROUP_CONCAT(IFNULL(c.synchronized, 'NULL') ORDER BY c.id SEPARATOR "\\n") AS csynchronizeds
		FROM Rapsys\AirBundle\Entity\GoogleToken AS t
		JOIN Rapsys\AirBundle\Entity\GoogleCalendar AS c ON (c.google_token_id = t.id)
		GROUP BY t.id
		ORDER BY NULL
	) AS a
	LEFT JOIN Rapsys\AirBundle\Entity\UserDance AS ud ON (ud.user_id = a.uid)
) AS b
LEFT JOIN Rapsys\AirBundle\Entity\UserSubscription AS us ON (us.user_id = b.uid)
SQL;

		//Replace bundle entity name by table name
		$req = str_replace($this->tableKeys, $this->tableValues, $req);

		//Get result set mapping instance
		//XXX: DEBUG: see ../blog.orig/src/Rapsys/BlogBundle/Repository/ArticleRepository.php
		$rsm = new ResultSetMapping();

		//Declare all fields
		//XXX: see vendor/doctrine/dbal/lib/Doctrine/DBAL/Types/Types.php
		//addScalarResult($sqlColName, $resColName, $type = 'string');
		$rsm
			->addScalarResult('tid', 'tid', 'integer')
			->addScalarResult('gmail', 'gmail', 'string')
			->addScalarResult('uid', 'uid', 'integer')
			->addScalarResult('access', 'access', 'string')
			->addScalarResult('refresh', 'refresh', 'string')
			->addScalarResult('expired', 'expired', 'datetime')
			->addScalarResult('cids', 'cids', 'string')
			->addScalarResult('cmails', 'cmails', 'string')
			->addScalarResult('csummaries', 'csummaries', 'string')
			->addScalarResult('csynchronizeds', 'csynchronizeds', 'string')
			->addScalarResult('dids', 'dids', 'string')
			->addScalarResult('sids', 'sids', 'string')
			->addIndexByScalar('tid');

		//Set result array
		$result = [];

		//Get tokens
		$tokens = $this->_em
			->createNativeQuery($req, $rsm)
			->getArrayResult();

		//Iterate on tokens
		foreach($tokens as $tid => $token) {
			//Set cids
			$cids = explode("\n", $token['cids']);

			//Set cmails
			$cmails = explode("\n", $token['cmails']);

			//Set csummaries
			$csummaries = explode("\n", $token['csummaries']);

			//Set csynchronizeds
			$csynchronizeds = array_map(function($v){return new \DateTime($v);}, explode("\n", $token['csynchronizeds']));

			//Set result
			$result[$tid] = [
				'id' => $tid,
				'mail' => $token['gmail'],
				'uid' => $token['uid'],
				'access' => $token['access'],
				'refresh' => $token['refresh'],
				'expired' => $token['expired'],
				'calendars' => [],
				'dances' => [],
				'subscriptions' => []
			];

			//Iterate on calendars
			foreach($cids as $k => $cid) {
				$result[$tid]['calendars'][$cid] = [
					'id' => $cid,
					'mail' => $cmails[$k],
					'summary' => $csummaries[$k],
					'synchronized' => $csynchronizeds[$k]
				];
			}

			//Set dids
			$dids = explode("\n", $token['dids']);

			//Iterate on dances
			foreach($dids as $k => $did) {
				$result[$tid]['dances'][$did] = [
					'id' => $did
				];
			}

			//Set sids
			$sids = explode("\n", $token['sids']);

			//Iterate on subscriptions
			foreach($sids as $k => $sid) {
				$result[$tid]['subscriptions'][$sid] = [
					'id' => $sid
				];
			}
		}

		//Return result
		return $result;
	}
}
