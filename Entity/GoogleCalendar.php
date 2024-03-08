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
 * GoogleCalendar
 */
class GoogleCalendar {
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
	 * Constructor
	 *
	 * @param GoogleToken $googleToken The google token
	 * @param string $mail The google calendar id
	 * @param string $summary The google calendar summary
	 * @param \DateTime $synchronized The google calendar last synchronization
	 */
	public function __construct(private GoogleToken $googleToken, private string $mail, private string $summary, private \DateTime $synchronized = new \DateTime('now')) {
		//Set defaults
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
	}

	/**
	 * Get id
	 *
	 * @return ?int
	 */
	public function getId(): ?int {
		return $this->id;
	}

	/**
	 * Set mail
	 *
	 * @param string $mail
	 * @return GoogleCalendar
	 */
	public function setMail(string $mail): GoogleCalendar {
		$this->mail = $mail;

		return $this;
	}

	/**
	 * Get mail
	 *
	 * @return string
	 */
	public function getMail(): string {
		return $this->mail;
	}

	/**
	 * Set summary
	 *
	 * @param string $summary
	 * @return GoogleCalendar
	 */
	public function setSummary(string $summary): GoogleCalendar {
		$this->summary = $summary;

		return $this;
	}

	/**
	 * Get summary
	 *
	 * @return string
	 */
	public function getSummary(): string {
		return $this->summary;
	}

	/**
	 * Set synchronized
	 *
	 * @param \DateTime $synchronized
	 *
	 * @return GoogleCalendar
	 */
	public function setSynchronized(\DateTime $synchronized): GoogleCalendar {
		$this->synchronized = $synchronized;

		return $this;
	}

	/**
	 * Get synchronized
	 *
	 * @return \DateTime
	 */
	public function getSynchronized(): \DateTime {
		return $this->synchronized;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return GoogleCalendar
	 */
	public function setCreated(\DateTime $created): GoogleCalendar {
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
	 * @return GoogleCalendar
	 */
	public function setUpdated(\DateTime $updated): GoogleCalendar {
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
	 * Set google token
	 *
	 * @param \Rapsys\AirBundle\Entity\GoogleToken $googleToken
	 *
	 * @return GoogleCalendar
	 */
	public function setGoogleToken(GoogleToken $googleToken): GoogleCalendar {
		$this->googleToken = $googleToken;

		return $this;
	}

	/**
	 * Get google token
	 *
	 * @return \Rapsys\AirBundle\Entity\GoogleToken
	 */
	public function getGoogleToken(): GoogleToken {
		return $this->googleToken;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs): ?GoogleCalendar {
		//Check that we have an snippet instance
		if (($entity = $eventArgs->getObject()) instanceof GoogleCalendar) {
			//Set updated value
			return $entity->setUpdated(new \DateTime('now'));
		}
	}
}
