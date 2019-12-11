<?php

namespace Rapsys\AirBundle\Entity;

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
	private $short;

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
	 * @var \DateTime
	 */
	private $created;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $sessions;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->sessions = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Location
	 */
	public function setTitle($title) {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Set short
	 *
	 * @param string $short
	 *
	 * @return Location
	 */
	public function setShort($short) {
		$this->short = $short;

		return $this;
	}

	/**
	 * Get short
	 *
	 * @return string
	 */
	public function getShort() {
		return $this->short;
	}

	/**
	 * Set address
	 *
	 * @param string $address
	 *
	 * @return Location
	 */
	public function setAddress($address) {
		$this->address = $address;

		return $this;
	}

	/**
	 * Get address
	 *
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}

	/**
	 * Set zipcode
	 *
	 * @param string $zipcode
	 *
	 * @return Location
	 */
	public function setZipcode($zipcode) {
		$this->zipcode = $zipcode;

		return $this;
	}

	/**
	 * Get zipcode
	 *
	 * @return string
	 */
	public function getZipcode() {
		return $this->zipcode;
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 *
	 * @return Location
	 */
	public function setCity($city) {
		$this->city = $city;

		return $this;
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}

	/**
	 * Set latitude
	 *
	 * @param string $latitude
	 *
	 * @return Location
	 */
	public function setLatitude($latitude) {
		$this->latitude = $latitude;

		return $this;
	}

	/**
	 * Get latitude
	 *
	 * @return string
	 */
	public function getLatitude() {
		return $this->latitude;
	}

	/**
	 * Set longitude
	 *
	 * @param string $longitude
	 *
	 * @return Location
	 */
	public function setLongitude($longitude) {
		$this->longitude = $longitude;

		return $this;
	}

	/**
	 * Get longitude
	 *
	 * @return string
	 */
	public function getLongitude() {
		return $this->longitude;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Location
	 */
	public function setCreated($created) {
		$this->created = $created;

		return $this;
	}

	/**
	 * Get created
	 *
	 * @return \DateTime
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * Set updated
	 *
	 * @param \DateTime $updated
	 *
	 * @return Location
	 */
	public function setUpdated($updated) {
		$this->updated = $updated;

		return $this;
	}

	/**
	 * Get updated
	 *
	 * @return \DateTime
	 */
	public function getUpdated() {
		return $this->updated;
	}

	/**
	 * Add session
	 *
	 * @param \Rapsys\AirBundle\Entity\Session $session
	 *
	 * @return Location
	 */
	public function addSession(\Rapsys\AirBundle\Entity\Session $session) {
		$this->sessions[] = $session;

		return $this;
	}

	/**
	 * Remove session
	 *
	 * @param \Rapsys\AirBundle\Entity\Session $session
	 */
	public function removeSession(\Rapsys\AirBundle\Entity\Session $session) {
		$this->sessions->removeElement($session);
	}

	/**
	 * Get sessions
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSessions() {
		return $this->sessions;
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
