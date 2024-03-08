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
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Dance
 */
class Dance {
	/**
	 * Primary key
	 */
	private ?int $id = null;

	/**
	 * Create datetime
	 */
	private \DateTime $created;

	/**
	 * Update datetime
	 */
	private \DateTime $updated;

	/**
	 * Applications collection
	 */
	private Collection $applications;

	/**
	 * Users collection
	 */
	private Collection $users;

	/**
	 * Constructor
	 *
	 * @param string $name The dance name
	 * @param string $type The dance type
	 */
	public function __construct(private string $name, private string $type) {
		//Set defaults
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');

		//Set collections
		$this->applications = new ArrayCollection();
		$this->users = new ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return Dance
	 */
	public function setName(string $name): Dance {
		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 *
	 * @return Dance
	 */
	public function setType(string $type): Dance {
		$this->type = $type;

		return $this;
	}

	/**
	 * Get type
	 *
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
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
		//Add from owning side
		$user->addDance($this);

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
		if (!$this->dances->contains($user)) {
			return true;
		}

		//Remove from owning side
		$user->removeDance($this);

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
		//Check that we have a dance instance
		if (($dance = $eventArgs->getObject()) instanceof Dance) {
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
		return $this->name.' '.lcfirst($this->type);
	}
}
