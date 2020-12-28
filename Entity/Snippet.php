<?php

namespace Rapsys\AirBundle\Entity;

use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\User;

/**
 * Snippet
 */
class Snippet {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	protected $locale;

	/**
	 * @var text
	 */
	protected $description;

	/**
	 * @var \DateTime
	 */
	protected $created;

	/**
	 * @var \DateTime
	 */
	protected $updated;

	/**
	 * @var \Rapsys\UserBundle\Entity\Location
	 */
	protected $location;

	/**
	 * @var \Rapsys\UserBundle\Entity\User
	 */
	protected $user;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set locale
	 *
	 * @param string $locale
	 *
	 * @return Snippet
	 */
	public function setLocale($locale) {
		$this->locale = $locale;

		return $this;
	}

	/**
	 * Get locale
	 *
	 * @return string
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return Snippet
	 */
	public function setDescription($description) {
		$this->description = $description;

		return $this;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return User
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
	 * @return User
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
	 * Set location
	 */
	public function setLocation(Location $location) {
		$this->location = $location;

		return $this;
	}

	/**
	 * Get location
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Set user
	 */
	public function setUser(User $user) {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 */
	public function getUser() {
		return $this->user;
	}
}
