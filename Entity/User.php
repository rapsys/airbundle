<?php

// src/Rapsys/AirBundle/Entity/User.php
namespace Rapsys\AirBundle\Entity;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Group;
use Rapsys\AirBundle\Entity\Link;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\UserBundle\Entity\User as BaseUser;

class User extends BaseUser {
	/**
	 * @var string
	 */
	protected $phone;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $applications;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $locations;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $snippets;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $subscribers;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $subscriptions;

	/**
	 * Constructor
	 *
	 * @param string $mail The user mail
	 */
	public function __construct(string $mail) {
		//Call parent constructor
		parent::__construct($mail);

		//Set collections
		$this->applications = new \Doctrine\Common\Collections\ArrayCollection();
		$this->locations = new \Doctrine\Common\Collections\ArrayCollection();
		$this->snippets = new \Doctrine\Common\Collections\ArrayCollection();
		$this->subscribers = new \Doctrine\Common\Collections\ArrayCollection();
		$this->subscriptions = new \Doctrine\Common\Collections\ArrayCollection();
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
	 * Set donation
	 *
	 * @param string $donation
	 *
	 * @return User
	 */
	public function setDonation($donation) {
		$this->donation = $donation;

		return $this;
	}

	/**
	 * Get donation
	 *
	 * @return string
	 */
	public function getDonation() {
		return $this->donation;
	}

	/**
	 * Set site
	 *
	 * @param string $site
	 *
	 * @return User
	 */
	public function setSite($site) {
		$this->site = $site;

		return $this;
	}

	/**
	 * Get site
	 *
	 * @return string
	 */
	public function getSite() {
		return $this->site;
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
	 * Add snippet
	 *
	 * @param \Rapsys\AirBundle\Entity\Snippet $snippet
	 *
	 * @return User
	 */
	public function addSnippet(Snippet $snippet) {
		$this->snippets[] = $snippet;

		return $this;
	}

	/**
	 * Remove snippet
	 *
	 * @param \Rapsys\AirBundle\Entity\Snippet $snippet
	 */
	public function removeSnippet(Snippet $snippet) {
		$this->snippets->removeElement($snippet);
	}

	/**
	 * Get snippets
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSnippets() {
		return $this->snippets;
	}

	/**
	 * Add location
	 *
	 * @param \Rapsys\AirBundle\Entity\Location $location
	 *
	 * @return User
	 */
	public function addLocation(Location $location) {
		$this->locations[] = $location;

		return $this;
	}

	/**
	 * Remove location
	 *
	 * @param \Rapsys\AirBundle\Entity\Location $location
	 */
	public function removeLocation(Location $location) {
		$this->locations->removeElement($location);
	}

	/**
	 * Get locations
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLocations() {
		return $this->locations;
	}

	/**
	 * Add subscriber
	 *
	 * @param \Rapsys\AirBundle\Entity\User $subscriber
	 *
	 * @return User
	 */
	public function addSubscriber(User $subscriber) {
		$this->subscribers[] = $subscriber;

		return $this;
	}

	/**
	 * Remove subscriber
	 *
	 * @param \Rapsys\AirBundle\Entity\User $subscriber
	 */
	public function removeSubscriber(User $subscriber) {
		$this->subscribers->removeElement($subscriber);
	}

	/**
	 * Get subscribers
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSubscribers() {
		return $this->subscribers;
	}

	/**
	 * Add subscription
	 *
	 * @param \Rapsys\AirBundle\Entity\User $subscription
	 *
	 * @return User
	 */
	public function addSubscription(User $subscription) {
		$this->subscriptions[] = $subscription;

		return $this;
	}

	/**
	 * Remove subscription
	 *
	 * @param \Rapsys\AirBundle\Entity\User $subscription
	 */
	public function removeSubscription(User $subscription) {
		$this->subscriptions->removeElement($subscription);
	}

	/**
	 * Get subscriptions
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSubscriptions() {
		return $this->subscriptions;
	}
}
