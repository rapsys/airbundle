<?php

namespace Rapsys\AirBundle\Entity;

/**
 * Application
 */
class Application {
	/**
	 * @var integer
	 */
	private $id;

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
	private $votes;

	/**
	 * @var \Rapsys\AirBundle\Entity\Session
	 */
	private $session;

	/**
	 * @var \Rapsys\AirBundle\Entity\User
	 */
	private $user;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->votes = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Application
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
	 * @return Application
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
	 * Add vote
	 *
	 * @param \Rapsys\AirBundle\Entity\Vote $vote
	 *
	 * @return Application
	 */
	public function addVote(\Rapsys\AirBundle\Entity\Vote $vote) {
		$this->votes[] = $vote;

		return $this;
	}

	/**
	 * Remove vote
	 *
	 * @param \Rapsys\AirBundle\Entity\Vote $vote
	 */
	public function removeVote(\Rapsys\AirBundle\Entity\Vote $vote) {
		$this->votes->removeElement($vote);
	}

	/**
	 * Get votes
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getVotes() {
		return $this->votes;
	}

	/**
	 * Set session
	 *
	 * @param \Rapsys\AirBundle\Entity\Session $session
	 *
	 * @return Application
	 */
	public function setSession(\Rapsys\AirBundle\Entity\Session $session = null) {
		$this->session = $session;

		return $this;
	}

	/**
	 * Get session
	 *
	 * @return \Rapsys\AirBundle\Entity\Session
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Set user
	 *
	 * @param \Rapsys\AirBundle\Entity\User $user
	 *
	 * @return Application
	 */
	public function setUser(\Rapsys\AirBundle\Entity\User $user = null) {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 *
	 * @return \Rapsys\AirBundle\Entity\User
	 */
	public function getUser() {
		return $this->user;
	}
}
