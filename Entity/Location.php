<?php declare(strict_types=1);

/*
 * this file is part of the rapsys packbundle package.
 *
 * (c) raphaël gertz <symfony@rapsys.eu>
 *
 * for the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Location
 */
class Location {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $address;

	/**
	 * @var string
	 */
	private $zipcode;

	/**
	 * @var string
	 */
	private $city;

	/**
	 * @var string
	 */
	private $latitude;

	/**
	 * @var string
	 */
	private $longitude;

	/**
	 * @var boolean
	 */
	private $hotspot;

	/**
	 * @var \DateTime
	 */
	private $created;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @var ArrayCollection
	 */
	private $sessions;

	/**
	 * @var ArrayCollection
	 */
	private $snippets;

	/**
	 * @var ArrayCollection
	 */
	private $users;

	/**
	 * Constructor
	 */
	public function __construct() {
		//Set defaults
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
		$this->sessions = new ArrayCollection();
		$this->snippets = new ArrayCollection();
		$this->users = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Location
	 */
	public function setTitle(string $title): Location {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Set address
	 *
	 * @param string $address
	 *
	 * @return Location
	 */
	public function setAddress(string $address): Location {
		$this->address = $address;

		return $this;
	}

	/**
	 * Get address
	 *
	 * @return string
	 */
	public function getAddress(): string {
		return $this->address;
	}

	/**
	 * Set zipcode
	 *
	 * @param string $zipcode
	 *
	 * @return Location
	 */
	public function setZipcode(string $zipcode): Location {
		$this->zipcode = $zipcode;

		return $this;
	}

	/**
	 * Get zipcode
	 *
	 * @return string
	 */
	public function getZipcode(): string {
		return $this->zipcode;
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 *
	 * @return Location
	 */
	public function setCity(string $city): Location {
		$this->city = $city;

		return $this;
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function getCity(): string {
		return $this->city;
	}

	/**
	 * Set latitude
	 *
	 * @param string $latitude
	 *
	 * @return Location
	 */
	public function setLatitude(string $latitude): Location {
		$this->latitude = $latitude;

		return $this;
	}

	/**
	 * Get latitude
	 *
	 * @return string
	 */
	public function getLatitude(): string {
		return $this->latitude;
	}

	/**
	 * Set longitude
	 *
	 * @param string $longitude
	 *
	 * @return Location
	 */
	public function setLongitude(string $longitude): Location {
		$this->longitude = $longitude;

		return $this;
	}

	/**
	 * Get longitude
	 *
	 * @return string
	 */
	public function getLongitude(): string {
		return $this->longitude;
	}

	/**
	 * Set hotspot
	 *
	 * @param boolean $hotspot
	 *
	 * @return Session
	 */
	public function setHotspot(bool $hotspot): Location {
		$this->hotspot = $hotspot;

		return $this;
	}

	/**
	 * Get hotspot
	 *
	 * @return boolean
	 */
	public function getHotspot(): bool {
		return $this->hotspot;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Location
	 */
	public function setCreated(\DateTime $created): Location {
		$this->created = $created;

		return $this;
	}

	/**
	 * Get created
	 *
	 * @return \DateTime
	 */
	public function getCreated(): \DateTime {
		return $this->created;
	}

	/**
	 * Set updated
	 *
	 * @param \DateTime $updated
	 *
	 * @return Location
	 */
	public function setUpdated(\DateTime $updated): Location {
		$this->updated = $updated;

		return $this;
	}

	/**
	 * Get updated
	 *
	 * @return \DateTime
	 */
	public function getUpdated(): \DateTime {
		return $this->updated;
	}

	/**
	 * Add session
	 *
	 * @param Session $session
	 *
	 * @return Location
	 */
	public function addSession(Session $session): Location {
		$this->sessions[] = $session;

		return $this;
	}

	/**
	 * Remove session
	 *
	 * @param Session $session
	 * @return boolean
	 */
	public function removeSession(Session $session): bool {
		return $this->sessions->removeElement($session);
	}

	/**
	 * Get sessions
	 *
	 * @return ArrayCollection
	 */
	public function getSessions(): ArrayCollection {
		return $this->sessions;
	}

	/**
	 * Add snippet
	 *
	 * @param Snippet $snippet
	 *
	 * @return Location
	 */
	public function addSnippet(Snippet $snippet): Location {
		$this->snippets[] = $snippet;

		return $this;
	}

	/**
	 * Remove snippet
	 *
	 * @param Snippet $snippet
	 * @return boolean
	 */
	public function removeSnippet(Snippet $snippet): bool {
		return $this->snippets->removeElement($snippet);
	}

	/**
	 * Get snippets
	 *
	 * @return ArrayCollection
	 */
	public function getSnippets(): ArrayCollection {
		return $this->snippets;
	}

	/**
	 * Add user
	 *
	 * @param User $user
	 *
	 * @return Location
	 */
	public function addUser(User $user): Location {
		$this->users[] = $user;

		return $this;
	}

	/**
	 * Remove user
	 *
	 * @param User $user
	 * @return boolean
	 */
	public function removeUser(User $user): bool {
		return $this->users->removeElement($user);
	}

	/**
	 * Get users
	 *
	 * @return ArrayCollection
	 */
	public function getUsers(): ArrayCollection {
		return $this->users;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		//Check that we have a location instance
		if (($location = $eventArgs->getEntity()) instanceof Location) {
			//Set updated value
			$location->setUpdated(new \DateTime('now'));
		}
	}

	/**
	 * Returns a string representation of the location
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->title;
	}
}
