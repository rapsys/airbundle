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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Country
 */
class Country {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @var string
	 */
	protected $alpha;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \DateTime
	 */
	protected $created;

	/**
	 * @var \DateTime
	 */
	protected $updated;

	/**
	 * @var ArrayCollection
	 */
	protected $users;

	/**
	 * Constructor
	 *
	 * @param string $code The country code
	 * @param string $alpha The country alpha
	 * @param string $title The country title
	 */
	public function __construct(string $code, string $alpha, string $title) {
		//Set defaults
		$this->code = $code;
		$this->alpha = $alpha;
		$this->title = $title;
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');

		//Set collections
		$this->users = new ArrayCollection();
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
	 * Set code
	 *
	 * @param string $code
	 *
	 * @return Country
	 */
	public function setCode(string $code): Country {
		$this->code = $code;

		return $this;
	}

	/**
	 * Get code
	 *
	 * @return string
	 */
	public function getCode(): string {
		return $this->code;
	}

	/**
	 * Set alpha
	 *
	 * @param string $alpha
	 *
	 * @return Country
	 */
	public function setAlpha(string $alpha): Country {
		$this->alpha = $alpha;

		return $this;
	}

	/**
	 * Get alpha
	 *
	 * @return string
	 */
	public function getAlpha(): string {
		return $this->alpha;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Country
	 */
	public function setTitle(string $title): Country {
		$this->title = $title;

		return $this;
	}

	/**
	 * Get title
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->title;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Country
	 */
	public function setCreated(\DateTime $created): Country {
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
	 * @return Country
	 */
	public function setUpdated(\DateTime $updated): Country {
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
	 * Add user
	 *
	 * @param User $user
	 *
	 * @return Country
	 */
	public function addUser(User $user): User {
		$this->users[] = $user;

		return $this;
	}

	/**
	 * Remove user
	 *
	 * @param User $user
	 */
	public function removeUser(User $user): bool {
		return $this->users->removeElement($user);
	}

	/**
	 * Get users
	 *
	 * @return ArrayCollection
	 */
	public function getUsers(): ArrayCollection {
		return $this->users;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		//Check that we have an country instance
		if (($country = $eventArgs->getEntity()) instanceof Country) {
			//Set updated value
			$country->setUpdated(new \DateTime('now'));
		}
	}

	/**
	 * Returns a string representation of the country
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->title;
	}
}
