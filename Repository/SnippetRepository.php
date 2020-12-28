<?php

namespace Rapsys\AirBundle\Repository;

use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * SnippetRepository
 */
class SnippetRepository extends \Doctrine\ORM\EntityRepository {
	/**
	 * Find snippets by locale and user id
	 *
	 * @param string $locale The locale
	 * @param User|int $user The user
	 * @return array The snippets or empty array
	 */
	public function findByLocaleUserId($locale, $user) {
		//Fetch snippets
		$ret = $this->getEntityManager()
			->createQuery('SELECT s FROM RapsysAirBundle:Snippet s WHERE s.locale = :locale and s.user = :user')
			->setParameter('locale', $locale)
			->setParameter('user', $user)
			->getResult();

		//Send result
		return $ret;
	}
}
