<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Rapsys\AirBundle\Entity\Civility;
use Rapsys\AirBundle\Entity\Group;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;

/**
 * {@inheritdoc}
 */
class AirFixtures extends Fixture {
	/**
	 * Air fixtures constructor
	 */
	public function __construct(protected ContainerInterface $container, protected UserPasswordHasherInterface $hasher) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function load(ObjectManager $manager) {
		//Civility tree
		$civilityTree = [
			'Mister',
			'Madam',
			'Miss'
		];

		//Create titles
		$civilitys = [];
		foreach($civilityTree as $civilityData) {
			$civility = new Civility($civilityData);
			$manager->persist($civility);
			$civilitys[$civilityData] = $civility;
			unset($civility);
		}

		//TODO: insert countries from https://raw.githubusercontent.com/raramuridesign/mysql-country-list/master/country-lists/mysql-country-list-detailed-info.sql
		#CREATE TABLE `countries` ( `id` int(10) unsigned NOT NULL AUTO_INCREMENT, `code` varchar(2) NOT NULL, `alpha` varchar(3) NOT NULL, `title` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL, `created` datetime NOT NULL, `updated` datetime NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `code` (`code`), UNIQUE KEY `alpha` (`alpha`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
		#insert into countries (code, alpha, title, created, updated) select countryCode, isoAlpha3, countryName, NOW(), NOW() FROM apps_countries_detailed ORDER BY countryCode ASC, isoAlpha3 ASC;

		//Dance tree
		$danceTree = [
			'Argentine Tango' => [
			   'Milonga', 'Class and milonga', 'Public class', 'Private class'
			]
		];

		//Create titles
		$dances = [];
		foreach($danceTree as $danceTitle => $danceData) {
			foreach($danceData as $danceType) {
				$dance = new Dance($danceTitle, $danceType);
				$manager->persist($dance);
				unset($dance);
			}
		}

		//Group tree
		//XXX: ROLE_XXX is required by
		$groupTree = [
			'User',
			'Guest',
			'Regular',
			'Senior',
			'Admin'
		];

		//Create groups
		$groups = [];
		foreach($groupTree as $groupData) {
			$group = new Group($groupData);
			$manager->persist($group);
			$groups[$groupData] = $group;
			unset($group);
		}

		//Flush to get the ids
		$manager->flush();

		//User tree
		$userTree = [
			[
				'short' => 'Mr.',
				'group' => 'Admin',
				'mail' => 'tango@rapsys.eu',
				'pseudonym' => 'Milonga Raphaël',
				'forename' => 'Raphaël',
				'surname' => 'Gertz',
				'phone' => '+33677952829',
				'password' => 'test'
			],
			/*[
				'short' => 'Mr.',
				'group' => 'Senior',
				'mail' => 'denis.courvoisier@wanadoo.fr',
				'pseudonym' => 'DJ Sined',
				'forename' => 'Denis',
				'surname' => 'Courvoisier',
				'phone' => '+33600000000',
				'password' => 'test'
			],*/
			[
				'short' => 'Mr.',
				'group' => 'Senior',
				'mail' => 'rannou402@orange.fr',
				'pseudonym' => 'Trio Tango',
				'forename' => 'Michel',
				'surname' => 'Rannou',
				'phone' => '+33600000000',
				'password' => 'test'
			],
			/*[
				'short' => 'Ms.',
				'group' => 'Regular',
				'mail' => 'roxmaps@gmail.com',
				'pseudonym' => 'Roxana',
				'forename' => 'Roxana',
				'surname' => 'Prado',
				'phone' => '+33600000000',
				'password' => 'test'
			],*/
		];

		//Create users
		$users = [];
		foreach($userTree as $userData) {
			$user = new User($userData['mail'], $userData['password'], $civilitys[$userData['short']], $userData['forename'], $userData['surname']);
			#TODO: check that password is hashed correctly !!!
			#$user->setPassword($this->hasher->hashPassword($user, $userData['password']));
			$user->setPseudonym($userData['pseudonym']);
			$user->setPhone($userData['phone']);
			$user->addGroup($groups[$userData['group']]);
			$manager->persist($user);
			$users[] = $user;
			unset($user);
		}

		//Flush to get the ids
		$manager->flush();

		//Location tree
		//XXX: adding a new zipcode here requires matching accuweather uris in Command/WeatherCommand.php
		//TODO: add descriptions as well
		$locationTree = [
			[
				'title' => 'Garnier opera',
				'short' => 'Garnier',
				'address' => '10 Place de l\'Opéra',
				'zipcode' => '75009',
				'city' => 'Paris',
				'latitude' => 48.871268,
				'longitude' => 2.331832,
				'hotspot' => true,
				'indoor' => false
			],
			[
				'title' => 'Tino-Rossi garden',
				'short' => 'Docks',
				'address' => '2 Quai Saint-Bernard',
				'zipcode' => '75005',
				'city' => 'Paris',
				'latitude' => 48.847736,
				'longitude' => 2.360953,
				'hotspot' => true,
				'indoor' => false
			],
			[
				'title' => 'Trocadero esplanade',
				'short' => 'Trocadero',
				'address' => '1 Avenue Hussein 1er de Jordanie',
				#75016 pour meteo-france, accuweather supporte 75116
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.861888,
				'longitude' => 2.288853,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Colette place',
				'short' => 'Colette',
				'address' => 'Galerie du Théâtre Français',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.863219,
				'longitude' => 2.335847,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Swan island',
				'short' => 'Swan',
				'address' => 'Allée des Cygnes',
				'zipcode' => '75015',
				'city' => 'Paris',
				'latitude' => 48.849976, #48.849976
				'longitude' => 2.279603, #2.2796029,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Jussieu esplanade',
				'short' => 'Jussieu',
				'address' => '25 rue des Fossés Saint-Bernard',
				'zipcode' => '75005',
				'city' => 'Paris',
				'latitude' => 48.847955, #48.8479548
				'longitude' => 2.353291, #2.3532907,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Orleans gallery',
				'short' => 'Orleans',
				'address' => '8 Galerie du Jardin',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.863885,
				'longitude' => 2.337387,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Orsay museum',
				'short' => 'Orsay',
				'address' => '1 rue de la Légion d\'Honneur',
				'zipcode' => '75007',
				'city' => 'Paris',
				'latitude' => 48.860418,
				'longitude' => 2.325815,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Saint-Honore market',
				'short' => 'Honore',
				'address' => '1 Passage des Jacobins',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.866992,
				'longitude' => 2.331752,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Igor Stravinsky place',
				'short' => 'Stravinsky',
				'address' => '2 rue Brisemiche',
				'zipcode' => '75004',
				'city' => 'Paris',
				'latitude' => 48.859244,
				'longitude' => 2.351289,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Tokyo palace',
				'short' => 'Tokyo',
				'address' => '14 Avenue de New York',
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.863827,
				'longitude' => 2.297339,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Drawings\' garden',
				'short' => 'Villette',
				'address' => 'Allée du Belvédère',
				'zipcode' => '75019',
				'city' => 'Paris',
				'latitude' => 48.892503,
				'longitude' => 2.389300,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Louvre palace',
				'short' => 'Louvre',
				'address' => 'Quai François Mitterrand',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.860386,
				'longitude' => 2.332611,
				'hotspot' => false,
				'indoor' => false
			],
			[
				'title' => 'Monde garden',
				'address' => '63 avenue Pierre Mendès-France',
				'zipcode' => '75013',
				'city' => 'Paris',
				'latitude' => 48.840451,
				'longitude' => 2.367638,
				'hotspot' => false,
				'indoor' => false
			]
		];

		//Create locations
		$locations = [];
		foreach($locationTree as $locationData) {
			$location = new Location($locationData['title'], $locationData['address'], $locationData['zipcode'], $locationData['city'], $locationData['latitude'], $locationData['longitude'], $locationData['hotspot'], $locationData['indoor']);
			$manager->persist($location);
			$locations[$locationData['title']] = $location;
			unset($location);
		}

		//Flush to get the ids
		$manager->flush();

		//Slot tree
		$slotTree = [
			'Morning',
			'Afternoon',
			'Evening',
			'After'
		];

		//Create slots
		$slots = [];
		foreach($slotTree as $slotData) {
			$slot = new Slot($slotData);
			$manager->persist($slot);
			$slots[$slot->getId()] = $slot;
			unset($slot);
		}

		//Flush to get the ids
		$manager->flush();
	}
}
