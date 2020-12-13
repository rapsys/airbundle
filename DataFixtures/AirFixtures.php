<?php

namespace Rapsys\AirBundle\DataFixtures;

use Rapsys\AirBundle\Entity\Title;
use Rapsys\AirBundle\Entity\Group;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;

class AirFixtures extends \Doctrine\Bundle\FixturesBundle\Fixture implements \Symfony\Component\DependencyInjection\ContainerAwareInterface {
	/**
	 * @var ContainerInterface
	 */
	private $container;

	public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null) {
		$this->container = $container;
	}

	/**
	 * {@inheritDoc}
	 */
	public function load(\Doctrine\Common\Persistence\ObjectManager $manager) {
		$encoder = $this->container->get('security.password_encoder');

		//Title tree
		$titleTree = array(
			'Mr.' => 'Mister',
			'Mrs.' => 'Madam',
			'Ms.' => 'Miss'
		);

		//Create titles
		$titles = array();
		foreach($titleTree as $shortData => $titleData) {
			$title = new Title();
			$title->setShort($shortData);
			$title->setTitle($titleData);
			$title->setCreated(new \DateTime('now'));
			$title->setUpdated(new \DateTime('now'));
			$manager->persist($title);
			$titles[$shortData] = $title;
			unset($title);
		}

		//Group tree
		//XXX: ROLE_XXX is required by
		$groupTree = array(
			'User',
			'Guest',
			'Regular',
			'Senior',
			'Admin'
		);

		//Create groups
		$groups = array();
		foreach($groupTree as $groupData) {
			$group = new Group($groupData);
			$group->setCreated(new \DateTime('now'));
			$group->setUpdated(new \DateTime('now'));
			$manager->persist($group);
			$groups[$groupData] = $group;
			unset($group);
		}

		//Flush to get the ids
		$manager->flush();

		//User tree
		$userTree = array(
			array(
				'short' => 'Mr.',
				'group' => 'Admin',
				'mail' => 'tango@rapsys.eu',
				'pseudonym' => 'Milonga Raphaël',
				'forename' => 'Raphaël',
				'surname' => 'Gertz',
				'phone' => '+33677952829',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'Senior',
				'mail' => 'denis.courvoisier@wanadoo.fr',
				'pseudonym' => 'DJ Sined',
				'forename' => 'Denis',
				'surname' => 'Courvoisier',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'Senior',
				'mail' => 'rannou402@orange.fr',
				'pseudonym' => 'Trio Tango',
				'forename' => 'Michel',
				'surname' => 'Rannou',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			/*array(
				'short' => 'Ms.',
				'group' => 'Regular',
				'mail' => 'roxmaps@gmail.com',
				'pseudonym' => 'Roxana',
				'forename' => 'Roxana',
				'surname' => 'Prado',
				'phone' => '+33600000000',
				'password' => 'test'
			),*/
		);

		//Create users
		$users = array();
		foreach($userTree as $userData) {
			$user = new User();
			$user->setMail($userData['mail']);
			$user->setPseudonym($userData['pseudonym']);
			$user->setForename($userData['forename']);
			$user->setSurname($userData['surname']);
			$user->setPhone($userData['phone']);
			$user->setPassword($encoder->encodePassword($user, $userData['password']));
			$user->setActive(true);
			$user->setTitle($titles[$userData['short']]);
			$user->addGroup($groups[$userData['group']]);
			$user->setCreated(new \DateTime('now'));
			$user->setUpdated(new \DateTime('now'));
			$manager->persist($user);
			$users[] = $user;
			unset($user);
		}

		//Flush to get the ids
		$manager->flush();

		//Location tree
		//XXX: adding a new zipcode here requires matching accuweather uris in Command/WeatherCommand.php
		$locationTree = [
			[
				'title' => 'Opera Garnier',
				'short' => 'Garnier',
				'address' => '10 Place de l\'Opéra',
				'zipcode' => '75009',
				'city' => 'Paris',
				'latitude' => 48.871268,
				'longitude' => 2.331832,
				'hotspot' => true
			],
			[
				'title' => 'Tino-Rossi garden',
				'short' => 'Docks',
				'address' => '2 Quai Saint-Bernard',
				'zipcode' => '75005',
				'city' => 'Paris',
				'latitude' => 48.847736,
				'longitude' => 2.360953,
				'hotspot' => true
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
				'hotspot' => false
			],
			[
				'title' => 'Colette square',
				'short' => 'Colette',
				'address' => 'Galerie du Théâtre Français',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.863219,
				'longitude' => 2.335847,
				'hotspot' => false
			],
			[
				'title' => 'Swan Island',
				'short' => 'Swan',
				'address' => 'Allée des Cygnes',
				'zipcode' => '75015',
				'city' => 'Paris',
				'latitude' => 48.849976, #48.849976
				'longitude' => 2.279603, #2.2796029,
				'hotspot' => false
			],
			[
				'title' => 'Jussieu esplanade',
				'short' => 'Jussieu',
				'address' => '25 rue des Fossés Saint-Bernard',
				'zipcode' => '75005',
				'city' => 'Paris',
				'latitude' => 48.847955, #48.8479548
				'longitude' => 2.353291, #2.3532907,
				'hotspot' => false
			],
			[
				'title' => 'Orleans gallery',
				'short' => 'Orleans',
				'address' => '8 Galerie du Jardin',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.863885,
				'longitude' => 2.337387,
				'hotspot' => false
			],
			[
				'title' => 'Orsay museum',
				'short' => 'Orsay',
				'address' => '1 rue de la Légion d\'Honneur',
				'zipcode' => '75007',
				'city' => 'Paris',
				'latitude' => 48.860418,
				'longitude' => 2.325815,
				'hotspot' => false
			],
			[
				'title' => 'Saint-Honore market',
				'short' => 'Honore',
				'address' => '1 Passage des Jacobins',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.866992,
				'longitude' => 2.331752,
				'hotspot' => false
			],
			[
				'title' => 'Igor Stravinsky place',
				'short' => 'Stravinsky',
				'address' => '2 rue Brisemiche',
				'zipcode' => '75004',
				'city' => 'Paris',
				'latitude' => 48.859244,
				'longitude' => 2.351289,
				'hotspot' => false
			],
			[
				'title' => 'Tokyo palace',
				'short' => 'Tokyo',
				'address' => '14 Avenue de New York',
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.863827,
				'longitude' => 2.297339,
				'hotspot' => false
			],
			[
				'title' => 'Drawings\' garden',
				'short' => 'Villette',
				'address' => 'Allée du Belvédère',
				'zipcode' => '75019',
				'city' => 'Paris',
				'latitude' => 48.892503,
				'longitude' => 2.389300,
				'hotspot' => false
			]
		];

		//Create locations
		$locations = array();
		foreach($locationTree as $locationData) {
			$location = new Location();
			$location->setTitle($locationData['title']);
			$location->setShort($locationData['short']);
			$location->setAddress($locationData['address']);
			$location->setZipcode($locationData['zipcode']);
			$location->setCity($locationData['city']);
			$location->setLatitude($locationData['latitude']);
			$location->setLongitude($locationData['longitude']);
			$location->setHotspot($locationData['hotspot']);
			$location->setCreated(new \DateTime('now'));
			$location->setUpdated(new \DateTime('now'));
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
		$slots = array();
		foreach($slotTree as $slotData) {
			$slot = new Slot();
			$slot->setTitle($slotData);
			$slot->setCreated(new \DateTime('now'));
			$slot->setUpdated(new \DateTime('now'));
			$manager->persist($slot);
			$slots[$slot->getId()] = $slot;
			unset($slot);
		}

		//Flush to get the ids
		$manager->flush();
	}
}
