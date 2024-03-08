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
 * Slot
 */
class Slot {
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
	 * Sessions collection
	 */
	private Collection $sessions;

	/**
	 * Constructor
	 */
	public function __construct(private string $title) {
		//Set defaults
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');

		//Set collections
		$this->sessions = new ArrayCollection();
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
	 * Set title
	 *
	 * @param string $title
	 *
	 * @return Title
	 */
	public function setTitle(string $title) {
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
	 * @return Slot
	 */
	public function setCreated(\DateTime $created) {
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
	 * @return Slot
	 */
	public function setUpdated(\DateTime $updated) {
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
	 * Add session
	 *
	 * @param Session $session
	 *
	 * @return Slot
	 */
	public function addSession(Session $session): Slot {
		$this->sessions[] = $session;

		return $this;
	}

	/**
	 * Remove session
	 *
	 * @param Session $session
	 */
	public function removeSession(Session $session): bool {
		return $this->sessions->removeElement($session);
	}

	/**
	 * Get sessions
	 *
	 * @return ArrayCollection
	 */
	public function getSessions(): ArrayCollection {
		return $this->sessions;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		//Check that we have a slot instance
		if (($slot = $eventArgs->getObject()) instanceof Slot) {
			//Set updated value
			$slot->setUpdated(new \DateTime('now'));
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
