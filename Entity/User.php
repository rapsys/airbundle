<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Rapsys\UserBundle\Entity\Civility;
use Rapsys\UserBundle\Entity\User as BaseUser;

/**
 * {@inheritdoc}
 */
class User extends BaseUser {
	/**
	 * City
	 */
	private ?string $city = null;

	/**
	 * Country
	 */
	private ?Country $country = null;

	/**
	 * Phone
	 */
	private ?string $phone = null;

	/**
	 * Pseudonym
	 */
	private ?string $pseudonym = null;

	/**
	 * Zipcode
	 */
	private ?string $zipcode = null;

	/**
	 * Applications collection
	 */
	private Collection $applications;

	/**
	 * Dances collection
	 */
	private Collection $dances;

	/**
	 * Locations collection
	 */
	private Collection $locations;

	/**
	 * Snippets collection
	 */
	private Collection $snippets;

	/**
	 * Subscribers collection
	 */
	private Collection $subscribers;

	/**
	 * Subscriptions collection
	 */
	private Collection $subscriptions;

	/**
	 * Google tokens collection
	 */
	private Collection $googleTokens;

	/**
	 * Constructor
	 *
	 * @param string $mail The user mail
	 * @param string $password The user password
	 * @param ?Civility $civility The user civility
	 * @param ?string $forename The user forename
	 * @param ?string $surname The user surname
	 * @param bool $active The user active
	 * @param bool $enable The user enable
	 */
	public function __construct(protected string $mail, protected string $password, protected ?Civility $civility = null, protected ?string $forename = null, protected ?string $surname = null, protected bool $active = false, protected bool $enable = true) {
		//Call parent constructor
		parent::__construct($this->mail, $this->password, $this->civility, $this->forename, $this->surname, $this->active, $this->enable);

		//Set collections
		$this->applications = new ArrayCollection();
		$this->dances = new ArrayCollection();
		$this->locations = new ArrayCollection();
		$this->snippets = new ArrayCollection();
		$this->subscribers = new ArrayCollection();
		$this->subscriptions = new ArrayCollection();
		$this->googleTokens = new ArrayCollection();
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 *
	 * @return User
	 */
	public function setCity(?string $city): User {
		$this->city = $city;

		return $this;
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function getCity(): ?string {
		return $this->city;
	}

	/**
	 * Set country
	 *
	 * @param Country $country
	 *
	 * @return User
	 */
	public function setCountry(?Country $country): User {
		$this->country = $country;

		return $this;
	}

	/**
	 * Get country
	 *
	 * @return Country
	 */
	public function getCountry(): ?Country {
		return $this->country;
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
	 * Set pseudonym
	 *
	 * @param string $pseudonym
	 *
	 * @return User
	 */
	public function setPseudonym(?string $pseudonym): User {
		$this->pseudonym = $pseudonym;

		return $this;
	}

	/**
	 * Get pseudonym
	 *
	 * @return string
	 */
	public function getPseudonym(): ?string {
		return $this->pseudonym;
	}

	/**
	 * Set zipcode
	 *
	 * @param string $zipcode
	 *
	 * @return User
	 */
	public function setZipcode(?string $zipcode): User {
		$this->zipcode = $zipcode;

		return $this;
	}

	/**
	 * Get zipcode
	 *
	 * @return string
	 */
	public function getZipcode(): ?string {
		return $this->zipcode;
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getApplications(): Collection {
		return $this->applications;
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getDances(): Collection {
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getLocations(): Collection {
		return $this->locations;
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSnippets(): Collection {
		return $this->snippets;
	}

	/**
	 * Add subscriber
	 *
	 * @param User $subscriber
	 *
	 * @return User
	 */
	public function addSubscriber(User $subscriber): User {
		//Add from owning side
		$subscriber->addSubscription($this);

		$this->subscribers[] = $subscriber;

		return $this;
	}

	/**
	 * Remove subscriber
	 *
	 * @param User $subscriber
	 */
	public function removeSubscriber(User $subscriber): bool {
		if (!$this->subscriptions->contains($subscriber)) {
			return true;
		}

		//Remove from owning side
		$subscriber->removeSubscription($this);

		return $this->subscribers->removeElement($subscriber);
	}

	/**
	 * Get subscribers
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSubscribers(): Collection {
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
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSubscriptions(): Collection {
		return $this->subscriptions;
	}

	/**
	 * Add google token
	 *
	 * @param GoogleToken $googleToken
	 *
	 * @return User
	 */
	public function addGoogleToken(GoogleToken $googleToken): User {
		$this->googleTokens[] = $googleToken;

		return $this;
	}

	/**
	 * Remove google token
	 *
	 * @param GoogleToken $googleToken
	 */
	public function removeGoogleToken(GoogleToken $googleToken): bool {
		return $this->googleTokens->removeElement($googleToken);
	}

	/**
	 * Get googleTokens
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getGoogleTokens(): Collection {
		return $this->googleTokens;
	}
}
