<?php

// src/Rapsys/AirBundle/Entity/User.php
namespace Rapsys\AirBundle\Entity;

class User extends \Rapsys\UserBundle\Entity\User {
	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $votes;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $applications;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Add vote
	 *
	 * @param \Rapsys\AirBundle\Entity\Vote $vote
	 *
	 * @return User
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
	 * Add application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 *
	 * @return User
	 */
	public function addApplication(\Rapsys\AirBundle\Entity\Application $application) {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 */
	public function removeApplication(\Rapsys\AirBundle\Entity\Application $application) {
		$this->applications->removeElement($application);
	}

	/**
	 * Get applications
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getApplications() {
		return $this->applications;
	}
}
