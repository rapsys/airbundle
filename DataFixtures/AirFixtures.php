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

	public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null)
	{
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
		$groupTree = array(
			'ROLE_USER',
			'ROLE_GUEST',
			'ROLE_REGULAR',
			'ROLE_SENIOR',
			'ROLE_ADMIN'
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
				'group' => 'ROLE_ADMIN',
				'mail' => 'tango@rapsys.eu',
				'pseudonym' => 'Rapsys',
				'forename' => 'Raphaël',
				'surname' => 'Gertz',
				'phone' => '+33677952829',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'ROLE_SENIOR',
				'mail' => 'rannou402@orange.fr',
				'pseudonym' => 'Mitch',
				'forename' => 'Michel',
				'surname' => 'Rannou',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			array(
				'short' => 'Ms.',
				'group' => 'ROLE_REGULAR',
				'mail' => 'roxmaps@gmail.com',
				'pseudonym' => 'Roxana',
				'forename' => 'Roxana',
				'surname' => 'Prado',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'ROLE_REGULAR',
				'mail' => 'majid.ghedjatti@gmail.com',
				'pseudonym' => 'El Guerrillero',
				'forename' => 'Majid',
				'surname' => 'Ghedjatti',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'ROLE_SENIOR',
				'mail' => 'denis.courvoisier@wanadoo.fr',
				'pseudonym' => 'Sined',
				'forename' => 'Denis',
				'surname' => 'Courvoisier',
				'phone' => '+33600000000',
				'password' => 'test'
			),
			array(
				'short' => 'Mr.',
				'group' => 'ROLE_REGULAR',
				'mail' => 'kastango13@gmail.com',
				'pseudonym' => 'Kastrat',
				'forename' => 'Kastrat',
				'surname' => 'Hasaj',
				'phone' => '+33600000000',
				'password' => 'test'
			),
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
		$locationTree = [
			[
				'title' => 'Opéra Garnier',
				'address' => '10 Place de l\'Opéra',
				'zipcode' => '75009',
				'city' => 'Paris',
				'latitude' => 48.871268,
				'longitude' => 2.331832
			],
			[
				'title' => 'Jardin Tino-Rossi',
				'address' => '2 Quai Saint-Bernard',
				'zipcode' => '75005',
				'city' => 'Paris',
				'latitude' => 48.847736,
				'longitude' => 2.360953
			],
			[
				'title' => 'Esplanade du Trocadéro',
				'address' => '1 Avenue Hussein 1er de Jordanie',
				#75016 pour meteo-france, accuweather supporte 75116
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.861888,
				'longitude' => 2.288853
			],
			[
				'title' => 'Marché Saint Honoré',
				'address' => '1 Passage des Jacobins',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.866992,
				'longitude' => 2.331752
			],
			[
				'title' => 'Palais de Tokyo',
				'address' => '14 Avenue de New York',
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.863827,
				'longitude' => 2.297339
			]
		];

		//Create locations
		$locations = array();
		foreach($locationTree as $locationData) {
			$location = new Location();
			$location->setTitle($locationData['title']);
			$location->setAddress($locationData['address']);
			$location->setZipcode($locationData['zipcode']);
			$location->setCity($locationData['city']);
			$location->setLatitude($locationData['latitude']);
			$location->setLongitude($locationData['longitude']);
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
			[
				'begin' => '14:00:00 UTC',
				'length' => '05:00:00'
			],
			[
				'begin' => '19:00:00 UTC',
				'length' => '06:00:00'
			],
			[
				'begin' => '19:00:00 UTC',
				'length' => '07:00:00'
			]
		];

		//Create slots
		$slots = array();
		foreach($slotTree as $slotData) {
			$slot = new Slot();
			$slot->setBegin(new \DateTime($slotData['begin']));
			$slot->setLength(new \DateTime($slotData['length']));
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
