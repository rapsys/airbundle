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

use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Application
 */
class Application {
	/**
	 * Primary key
	 */
	private ?int $id = null;

	/**
	 * Dance instance
	 */
	private Dance $dance;

	/**
	 * Score
	 */
	private ?float $score = null;

	/**
	 * Cancel datetime
	 */
	private ?\DateTime $canceled = null;

	/**
	 * Create datetime
	 */
	private \DateTime $created;

	/**
	 * Update datetime
	 */
	private \DateTime $updated;

	/**
	 * Session instance
	 */
	private $session = null;

	/**
	 * User instance
	 */
	private $user = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		//Set defaults
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
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
	 * Set dance
	 *
	 * @param Dance $dance
	 *
	 * @return Application
	 */
	public function setDance(Dance $dance): Application {
		$this->dance = $dance;

		return $this;
	}

	/**
	 * Get dance
	 *
	 * @return Dance
	 */
	public function getDance(): Dance {
		return $this->dance;
	}

	/**
	 * Set score
	 *
	 * @param float $score
	 *
	 * @return Application
	 */
	public function setScore(?float $score): Application {
		$this->score = $score;

		return $this;
	}

	/**
	 * Get score
	 *
	 * @return float
	 */
	public function getScore(): ?float {
		return $this->score;
	}

	/**
	 * Set canceled
	 *
	 * @param \DateTime $canceled
	 *
	 * @return Application
	 */
	public function setCanceled(?\DateTime $canceled): Application {
		$this->canceled = $canceled;

		return $this;
	}

	/**
	 * Get canceled
	 *
	 * @return \DateTime
	 */
	public function getCanceled(): ?\DateTime {
		return $this->canceled;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Application
	 */
	public function setCreated(\DateTime $created): Application {
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
	 * @return Application
	 */
	public function setUpdated(\DateTime $updated): Application {
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
	 * Set session
	 *
	 * @param Session $session
	 *
	 * @return Application
	 */
	public function setSession(Session $session): Application {
		$this->session = $session;

		return $this;
	}

	/**
	 * Get session
	 *
	 * @return Session
	 */
	public function getSession(): Session {
		return $this->session;
	}

	/**
	 * Set user
	 *
	 * @param User $user
	 *
	 * @return Application
	 */
	public function setUser(User $user): Application {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 *
	 * @return User
	 */
	public function getUser(): User {
		return $this->user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		//Check that we have an application instance
		if (($application = $eventArgs->getObject()) instanceof Application) {
			//Set updated value
			$application->setUpdated(new \DateTime('now'));
		}
	}
}
