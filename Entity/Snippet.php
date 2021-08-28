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

use Doctrine\ORM\Event\PreUpdateEventArgs;

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
	protected $short;

	/**
	 * @var integer
	 */
	protected $rate;

	/**
	 * @var bool
	 */
	protected $hat;

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
	 * @var Location
	 */
	protected $location;

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * Constructor
	 */
	public function __construct() {
		//Set defaults
		$this->description = null;
		$this->class = null;
		$this->short = null;
		$this->rate = null;
		$this->hat = null;
		$this->contact = null;
		$this->donate = null;
		$this->link = null;
		$this->profile = null;
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
		$this->location = null;
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
	 * Set locale
	 *
	 * @param string $locale
	 *
	 * @return Snippet
	 */
	public function setLocale(string $locale): Snippet {
		$this->locale = $locale;

		return $this;
	}

	/**
	 * Get locale
	 *
	 * @return string
	 */
	public function getLocale(): string {
		return $this->locale;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return Snippet
	 */
	public function setDescription(?string $description): Snippet {
		$this->description = $description;

		return $this;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription(): ?string {
		return $this->description;
	}

	/**
	 * Set class
	 *
	 * @param string $class
	 *
	 * @return Snippet
	 */
	public function setClass(?string $class): Snippet {
		$this->class = $class;

		return $this;
	}

	/**
	 * Get class
	 *
	 * @return string
	 */
	public function getClass(): ?string {
		return $this->class;
	}

	/**
	 * Set short
	 *
	 * @param string $short
	 *
	 * @return Snippet
	 */
	public function setShort(?string $short): Snippet {
		$this->short = $short;

		return $this;
	}

	/**
	 * Get short
	 *
	 * @return string
	 */
	public function getShort(): ?string {
		return $this->short;
	}

	/**
	 * Set rate
	 *
	 * @param int $rate
	 *
	 * @return Snippet
	 */
	public function setRate(?int $rate): Snippet {
		$this->rate = $rate;

		return $this;
	}

	/**
	 * Get rate
	 *
	 * @return int
	 */
	public function getRate(): ?int {
		return $this->rate;
	}

	/**
	 * Set hat
	 *
	 * @param bool $hat
	 *
	 * @return User
	 */
	public function setHat(?bool $hat): Snippet {
		$this->hat = $hat;

		return $this;
	}

	/**
	 * Get hat
	 *
	 * @return bool
	 */
	public function getHat(): ?bool {
		return $this->hat;
	}
	/**
	 * Set contact
	 *
	 * @param string $contact
	 *
	 * @return Snippet
	 */
	public function setContact(?string $contact): Snippet {
		$this->contact = $contact;

		return $this;
	}

	/**
	 * Get contact
	 *
	 * @return string
	 */
	public function getContact(): ?string {
		return $this->contact;
	}

	/**
	 * Set donate
	 *
	 * @param string $donate
	 *
	 * @return Snippet
	 */
	public function setDonate(?string $donate): Snippet {
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
	 * @return Snippet
	 */
	public function setLink(?string $link): Snippet {
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
	 * Set profile
	 *
	 * @param string $profile
	 *
	 * @return Snippet
	 */
	public function setProfile(?string $profile): Snippet {
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
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Snippet
	 */
	public function setCreated(\DateTime $created): Snippet {
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
	 * @return Snippet
	 */
	public function setUpdated(\DateTime $updated): Snippet {
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
