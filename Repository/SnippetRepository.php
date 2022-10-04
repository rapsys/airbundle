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

/**
 * SnippetRepository
 */
class SnippetRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find snippets by user id, locale and index by location id
	 *
	 * @param int $user The user
	 * @param string $locale The locale
	 * @return array The snippets array
	 */
	public function findByUserIdLocaleIndexByLocationId(int $userId, string $locale): array {
		//Fetch snippets
		$ret = $this->_em
			->createQuery('SELECT s FROM RapsysAirBundle:Snippet s INDEX BY s.location WHERE s.locale = :locale and s.user = :user')
			->setParameter('user', $userId)
			->setParameter('locale', $locale)
			->getResult();

		//Send result
		return $ret;
	}
}
