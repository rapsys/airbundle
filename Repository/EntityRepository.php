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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * EntityRepository
 *
 * {@inheritdoc}
 */
class EntityRepository extends BaseEntityRepository {
	/**
	 * The RouterInterface instance
	 *
	 * @var RouterInterface
	 */
	protected RouterInterface $router;

	/**
	 * The SluggerUtil instance
	 *
	 * @var SluggerUtil
	 */
	protected SluggerUtil $slugger;

	/**
	 * The table keys array
	 *
	 * @var array
	 */
	protected array $tableKeys;

	/**
	 * The table values array
	 *
	 * @var array
	 */
	protected array $tableValues;

	/**
	 * The TranslatorInterface instance
	 *
	 * @var TranslatorInterface
	 */
	protected TranslatorInterface $translator;

	/**
	 * The list of languages
	 *
	 * @var string[]
	 */
	protected array $languages = [];

	/**
	 * Initializes a new LocationRepository instance
	 *
	 * @param EntityManagerInterface $manager The EntityManagerInterface instance
	 * @param ClassMetadata $class The ClassMetadata instance
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The SluggerUtil instance
	 * @param TranslatorInterface $translator The TranslatorInterface instance
	 * @param array $languages The languages list
	 */
	public function __construct(EntityManagerInterface $manager, ClassMetadata $class, RouterInterface $router, SluggerUtil $slugger, TranslatorInterface $translator, array $languages) {
		//Call parent constructor
		parent::__construct($manager, $class);

		//Set languages
		$this->languages = $languages;

		//Set router
		$this->router = $router;

		//Set slugger
		$this->slugger = $slugger;

		//Set translator
		$this->translator = $translator;

		//Get quote strategy
		$qs = $manager->getConfiguration()->getQuoteStrategy();
		$dp = $manager->getConnection()->getDatabasePlatform();

		//Set quoted table names
		//XXX: this allow to make this code table name independent
		//XXX: remember to place longer prefix before shorter to avoid strange replacings
		$tables = [
			'RapsysAirBundle:UserGroup' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:UserLocation' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('locations'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Application' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Civility' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Civility'), $dp),
			'RapsysAirBundle:Country' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Country'), $dp),
			'RapsysAirBundle:Dance' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Dance'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:User'), $dp),

			//XXX: Set limit used to workaround mariadb subselect optimization
			':limit' => PHP_INT_MAX,
			':afterid' => 4,
			"\t" => '',
			"\n" => ' '
		];

		//Set quoted table name keys
		$this->tableKeys = array_keys($tables);

		//Set quoted table name values
		$this->tableValues = array_values($tables);
	}
}
