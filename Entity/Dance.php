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
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Dance
 */
class Dance {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var \DateTime
	 */
	private $created;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @var ArrayCollection
	 */
	private $applications;

	/**
	 * @var ArrayCollection
	 */
	private $users;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->applications = new ArrayCollection();
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
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Dance
	 */
	public function setTitle(string $title): Dance {
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
	 * @return Dance
	 */
	public function setCreated(\DateTime $created): Dance {
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
	 * @return Dance
	 */
	public function setUpdated(\DateTime $updated): Dance {
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
	 * Add application
	 *
	 * @param Application $application
	 *
	 * @return Dance
	 */
	public function addApplication(Application $application): Dance {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param Application $application
	 *
	 * @return bool
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
	 * Add user
	 *
	 * @param User $user
	 *
	 * @return Dance
	 */
	public function addUser(User $user): Dance {
		$this->users[] = $user;

		return $this;
	}

	/**
	 * Remove user
	 *
	 * @param User $user
	 *
	 * @return bool
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
		//Check that we have an session instance
		if (($dance = $eventArgs->getEntity()) instanceof Dance) {
			//Set updated value
			$dance->setUpdated(new \DateTime('now'));
		}
	}

	/**
	 * Returns a string representation of the slot
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->title;
	}
}
