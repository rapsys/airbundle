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
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $class;

	/**
	 * @var string
	 */
	protected $contact;

	/**
	 * @var string
	 */
	protected $donate;

	/**
	 * @var string
	 */
	protected $link;

	/**
	 * @var string
	 */
	protected $profile;

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
	 * Set class
	 *
	 * @param string $class
	 *
	 * @return Snippet
	 */
	public function setClass($class) {
		$this->class = $class;

		return $this;
	}

	/**
	 * Get class
	 *
	 * @return string
	 */
	public function getClass() {
		return $this->class;
	}

	/**
	 * Set contact
	 *
	 * @param string $contact
	 *
	 * @return Snippet
	 */
	public function setContact($contact) {
		$this->contact = $contact;

		return $this;
	}

	/**
	 * Get contact
	 *
	 * @return string
	 */
	public function getContact() {
		return $this->contact;
	}

	/**
	 * Set donate
	 *
	 * @param string $donate
	 *
	 * @return Snippet
	 */
	public function setDonate($donate) {
		$this->donate = $donate;

		return $this;
	}

	/**
	 * Get donate
	 *
	 * @return string
	 */
	public function getDonate() {
		return $this->donate;
	}

	/**
	 * Set link
	 *
	 * @param string $link
	 *
	 * @return Snippet
	 */
	public function setLink($link) {
		$this->link = $link;

		return $this;
	}

	/**
	 * Get link
	 *
	 * @return string
	 */
	public function getLink() {
		return $this->link;
	}

	/**
	 * Set profile
	 *
	 * @param string $profile
	 *
	 * @return Snippet
	 */
	public function setProfile($profile) {
		$this->profile = $profile;

		return $this;
	}

	/**
	 * Get profile
	 *
	 * @return string
	 */
	public function getProfile() {
		return $this->profile;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Snippet
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
	 * @return Snippet
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
	 *
	 * @param Location $location
	 *
	 * @return Snippet
	 */
	public function setLocation(Location $location) {
		$this->location = $location;

		return $this;
	}

	/**
	 * Get location
	 *
	 * @return Location
	 */
	public function getLocation() {
		return $this->location;
	}

	/**
	 * Set user
	 *
	 * @param User $user
	 *
	 * @return Snippet
	 */
	public function setUser(User $user) {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 *
	 * @return User
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs  $eventArgs) {
		//Check that we have an snippet instance
		if (($snippet = $eventArgs->getEntity()) instanceof Snippet) {
			//Set updated value
			$snippet->setUpdated(new \DateTime('now'));
		}
	}
}
