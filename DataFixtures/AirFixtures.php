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
			'M.' => 'Monsieur',
			'Mlle' => 'Mademoiselle',
			'Mme' => 'Madame'
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
			'ROLE_ADMIN',
			'ROLE_SUPER'
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
				'short' => 'M.',
				'group' => 'ROLE_SUPER',
				'mail' => 'airlibre@rapsys.eu',
				'pseudonym' => 'Rapsys',
				'forename' => 'Raphaël',
				'surname' => 'Gertz',
				'password' => 'test'
			),
			array(
				'short' => 'M.',
				'group' => 'ROLE_ADMIN',
				'mail' => 'rannou402@orange.fr',
				'pseudonym' => 'Mitch',
				'forename' => 'Michel',
				'surname' => 'Rannou',
				'password' => 'test'
			),
			array(
				'short' => 'Mlle',
				'group' => 'ROLE_ADMIN',
				'mail' => 'roxmaps@gmail.com',
				'pseudonym' => 'Roxana',
				'forename' => 'Roxana',
				'surname' => 'Prado',
				'password' => 'test'
			),
			array(
				'short' => 'M.',
				'group' => 'ROLE_ADMIN',
				'mail' => 'majid.ghedjatti@gmail.com',
				'pseudonym' => 'El Guerrillero',
				'forename' => 'Majid',
				'surname' => 'Ghedjatti',
				'password' => 'test'
			),
			array(
				'short' => 'M.',
				'group' => 'ROLE_ADMIN',
				'mail' => 'denis.courvoisier@wanadoo.fr',
				'pseudonym' => 'Sined',
				'forename' => 'Denis',
				'surname' => 'Courvoisier',
				'password' => 'test'
			),
			array(
				'short' => 'M.',
				'group' => 'ROLE_ADMIN',
				'mail' => 'kastango13@gmail.com',
				'pseudonym' => 'Kastrat',
				'forename' => 'Kastrat',
				'surname' => 'Hasaj',
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
				'title' => 'Esplanade du Trocadéro',
				'address' => '1 Avenue Hussein 1er de Jordanie',
				#75016 pour meteo-france, accuweather supporte 75116
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.8619,
				'longitude' => 2.2888
			],
			[
				'title' => 'Opéra Garnier',
				'address' => 'Place de l\'Opéra',
				'zipcode' => '75009',
				'city' => 'Paris',
				'latitude' => 48.871365,
				'longitude' => 2.332026
			],
			[
				'title' => 'Marché Saint Honoré',
				'address' => '1 Passage des Jacobins',
				'zipcode' => '75001',
				'city' => 'Paris',
				'latitude' => 48.8668,
				'longitude' => 2.331659
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
				'title' => 'Palais de Tokyo',
				'address' => '13 Avenue du Président Wilson',
				'zipcode' => '75116',
				'city' => 'Paris',
				'latitude' => 48.864567,
				'longitude' => 2.296892
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
				'end' => '19:00:00 UTC'
			],
			[
				'begin' => '19:00:00 UTC',
				'end' => '23:00:00 UTC'
			],
			[
				'begin' => '23:00:00 UTC',
				'end' => '02:00:00 UTC'
			]
		];

		//Create slots
		$slots = array();
		foreach($slotTree as $slotData) {
			$slot = new Slot();
			$slot->setBegin(new \DateTime($slotData['begin']));
			$slot->setEnd(new \DateTime($slotData['end']));
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
