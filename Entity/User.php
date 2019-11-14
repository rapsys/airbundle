<?php

// src/Rapsys/AirBundle/Entity/User.php
namespace Rapsys\AirBundle\Entity;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Group;
use Rapsys\AirBundle\Entity\Vote;
use Rapsys\UserBundle\Entity\User as BaseUser;

class User extends BaseUser {
	/**
	 * @var string
	 */
	protected $phone;

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
	 * Set phone
	 *
	 * @param string $phone
	 *
	 * @return User
	 */
	public function setPhone($phone) {
		$this->phone = $phone;

		return $this;
	}

	/**
	 * Get phone
	 *
	 * @return string
	 */
	public function getPhone() {
		return $this->phone;
	}

	/**
	 * Add vote
	 *
	 * @param \Rapsys\AirBundle\Entity\Vote $vote
	 *
	 * @return User
	 */
	public function addVote(Vote $vote) {
		$this->votes[] = $vote;

		return $this;
	}

	/**
	 * Remove vote
	 *
	 * @param \Rapsys\AirBundle\Entity\Vote $vote
	 */
	public function removeVote(Vote $vote) {
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
	public function addApplication(Application $application) {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 */
	public function removeApplication(Application $application) {
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

	/**
	 * Get roles
	 *
	 * @return array
	 */
	public function getRoles() {
		//Return roles array
		//XXX: [ ROLE_USER, ROLE_XXX, ... ]
		return parent::getRoles();
	}
}
