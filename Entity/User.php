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

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Group;
use Rapsys\AirBundle\Entity\Link;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\UserBundle\Entity\User as BaseUser;

class User extends BaseUser {
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
	protected $phone;

	/**
	 * @var string
	 */
	protected $profile;

	/**
	 * @var ArrayCollection
	 */
	private $applications;

	/**
	 * @var ArrayCollection
	 */
	private $dances;

	/**
	 * @var ArrayCollection
	 */
	private $locations;

	/**
	 * @var ArrayCollection
	 */
	private $snippets;

	/**
	 * @var ArrayCollection
	 */
	private $subscribers;

	/**
	 * @var ArrayCollection
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
		$this->applications = new ArrayCollection();
		$this->dances = new ArrayCollection();
		$this->locations = new ArrayCollection();
		$this->snippets = new ArrayCollection();
		$this->subscribers = new ArrayCollection();
		$this->subscriptions = new ArrayCollection();
	}

	/**
	 * Set donate
	 *
	 * @param string $donate
	 *
	 * @return User
	 */
	public function setDonate(?string $donate): User {
		$this->donate = $donate;

		return $this;
	}

	/**
	 * Get donate
	 *
	 * @return string
	 */
	public function getDonate(): ?string {
		return $this->donate;
	}

	/**
	 * Set link
	 *
	 * @param string $link
	 *
	 * @return User
	 */
	public function setLink(?string $link): User {
		$this->link = $link;

		return $this;
	}

	/**
	 * Get link
	 *
	 * @return string
	 */
	public function getLink(): ?string {
		return $this->link;
	}

	/**
	 * Set phone
	 *
	 * @param string $phone
	 *
	 * @return User
	 */
	public function setPhone(?string $phone): User {
		$this->phone = $phone;

		return $this;
	}

	/**
	 * Get phone
	 *
	 * @return string
	 */
	public function getPhone(): ?string {
		return $this->phone;
	}

	/**
	 * Set profile
	 *
	 * @param string $profile
	 *
	 * @return User
	 */
	public function setProfile(string $profile): User {
		$this->profile = $profile;

		return $this;
	}

	/**
	 * Get profile
	 *
	 * @return string
	 */
	public function getProfile(): ?string {
		return $this->profile;
	}

	/**
	 * Add application
	 *
	 * @param Application $application
	 *
	 * @return User
	 */
	public function addApplication(Application $application): User {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param Application $application
	 */
	public function removeApplication(Application $application): bool {
		return $this->applications->removeElement($application);
	}

	/**
	 * Get applications
	 *
	 * @return ArrayCollection
	 */
	public function getApplications(): ArrayCollection {
		return $this->applications;
	}

	/**
	 * Add snippet
	 *
	 * @param Snippet $snippet
	 *
	 * @return User
	 */
	public function addSnippet(Snippet $snippet): User {
		$this->snippets[] = $snippet;

		return $this;
	}

	/**
	 * Remove snippet
	 *
	 * @param Snippet $snippet
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
	 * Add dance
	 *
	 * @param Dance $dance
	 *
	 * @return User
	 */
	public function addDance(Dance $dance): User {
		$this->dances[] = $dance;

		return $this;
	}

	/**
	 * Remove dance
	 *
	 * @param Dance $dance
	 *
	 * @return bool
	 */
	public function removeDance(Dance $dance): bool {
		return $this->dances->removeElement($dance);
	}

	/**
	 * Get dances
	 *
	 * @return ArrayCollection
	 */
	public function getDances(): ArrayCollection {
		return $this->dances;
	}

	/**
	 * Add location
	 *
	 * @param Location $location
	 *
	 * @return User
	 */
	public function addLocation(Location $location): User {
		$this->locations[] = $location;

		return $this;
	}

	/**
	 * Remove location
	 *
	 * @param Location $location
	 */
	public function removeLocation(Location $location): bool {
		return $this->locations->removeElement($location);
	}

	/**
	 * Get locations
	 *
	 * @return ArrayCollection
	 */
	public function getLocations(): ArrayCollection {
		return $this->locations;
	}

	/**
	 * Add subscriber
	 *
	 * @param User $subscriber
	 *
	 * @return User
	 */
	public function addSubscriber(User $subscriber): User {
		$this->subscribers[] = $subscriber;

		return $this;
	}

	/**
	 * Remove subscriber
	 *
	 * @param User $subscriber
	 */
	public function removeSubscriber(User $subscriber): bool {
		return $this->subscribers->removeElement($subscriber);
	}

	/**
	 * Get subscribers
	 *
	 * @return ArrayCollection
	 */
	public function getSubscribers(): ArrayCollection {
		return $this->subscribers;
	}

	/**
	 * Add subscription
	 *
	 * @param User $subscription
	 *
	 * @return User
	 */
	public function addSubscription(User $subscription): User {
		$this->subscriptions[] = $subscription;

		return $this;
	}

	/**
	 * Remove subscription
	 *
	 * @param User $subscription
	 */
	public function removeSubscription(User $subscription): bool {
		return $this->subscriptions->removeElement($subscription);
	}

	/**
	 * Get subscriptions
	 *
	 * @return ArrayCollection
	 */
	public function getSubscriptions(): ArrayCollection {
		return $this->subscriptions;
	}
}
