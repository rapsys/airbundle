<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * Repository
 *
 * {@inheritdoc}
 */
class Repository extends EntityRepository {
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
	 * Initializes a new LocationRepository instance
	 *
	 * @param EntityManagerInterface $manager The EntityManagerInterface instance
	 * @param ClassMetadata $class The ClassMetadata instance
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The SluggerUtil instance
	 * @param TranslatorInterface $translator The TranslatorInterface instance
	 * @param string $locale The current locale
	 * @param array $languages The languages list
	 */
	public function __construct(protected EntityManagerInterface $manager, protected ClassMetadata $class, protected RouterInterface $router, protected SluggerUtil $slugger, protected TranslatorInterface $translator, protected string $locale, protected array $languages) {
		//Call parent constructor
		parent::__construct($manager, $class);

		//Get quote strategy
		$qs = $this->manager->getConfiguration()->getQuoteStrategy();
		$dp = $this->manager->getConnection()->getDatabasePlatform();

		//Set quoted table names
		//XXX: this allow to make this code table name independent
		//XXX: remember to place longer prefix before shorter to avoid strange replacings
		$tables = [
			'RapsysAirBundle:UserDance' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('dances'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:UserGroup' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('groups'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:UserLocation' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('locations'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:UserSubscription' => $qs->getJoinTableName($manager->getClassMetadata('RapsysAirBundle:User')->getAssociationMapping('subscriptions'), $manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			'RapsysAirBundle:Application' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Application'), $dp),
			'RapsysAirBundle:Civility' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Civility'), $dp),
			'RapsysAirBundle:Country' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Country'), $dp),
			'RapsysAirBundle:Dance' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Dance'), $dp),
			'RapsysAirBundle:GoogleCalendar' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:GoogleCalendar'), $dp),
			'RapsysAirBundle:GoogleToken' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:GoogleToken'), $dp),
			'RapsysAirBundle:Group' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Group'), $dp),
			'RapsysAirBundle:Location' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Location'), $dp),
			'RapsysAirBundle:Session' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Session'), $dp),
			'RapsysAirBundle:Slot' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Slot'), $dp),
			'RapsysAirBundle:Snippet' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:Snippet'), $dp),
			'RapsysAirBundle:User' => $qs->getTableName($manager->getClassMetadata('RapsysAirBundle:User'), $dp),
			//Set accuweather max number of daily pages
			':accudaily' => 12,
			//Set accuweather max number of hourly pages
			':accuhourly' => 3,
			//Set guest delay
			':guestdelay' => 2 * 24 * 3600,
			//Set regular delay
			':regulardelay' => 3 * 24 * 3600,
			//Set senior delay
			':seniordelay' => 4 * 24 * 3600,
			//Set guest group id
			':guestid' => 2,
			//Set regular group id
			':regularid' => 3,
			//Set senior group id
			':seniorid' => 4,
			//Set afternoon slot id
			':afternoonid' => 2,
			//Set evening slot id
			':eveningid' => 3,
			//Set after slot id
			':afterid' => 4,
			//XXX: days since last session after which guest regain normal priority
			':guestwait' => 30,
			//XXX: session count until considered at regular delay
			':scount' => 5,
			//XXX: pn_ratio over which considered at regular delay
			':pnratio' => 1,
			//XXX: tr_ratio diff over which considered at regular delay
			':trdiff' => 5,
			//Set locale
			//XXX: or $manager->getConnection()->quote($this->locale) ???
			':locale' => $dp->quoteStringLiteral($this->locale),
			//XXX: Set limit used to workaround mariadb subselect optimization
			':limit' => PHP_INT_MAX,
			"\t" => '',
			"\n" => ' '
		];

		//Set quoted table name keys
		$this->tableKeys = array_keys($tables);

		//Set quoted table name values
		$this->tableValues = array_values($tables);
	}
}
