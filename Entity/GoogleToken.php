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
 * GoogleToken
 */
class GoogleToken {
	/**
	 * @var int
	 */
	private ?int $id;

	/**
	 * @var string
	 */
	private string $mail;

	/**
	 * @var string
	 */
	private string $access;

	/**
	 * @var ?string
	 */
	private ?string $refresh;

	/**
	 * @var \DateTime
	 */
	private \DateTime $expired;

	/**
	 * @var \DateTime
	 */
	private \DateTime $created;

	/**
	 * @var \DateTime
	 */
	private \DateTime $updated;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private Collection $googleCalendars;

	/**
	 * @var \Rapsys\AirBundle\Entity\User
	 */
	private User $user;

	/**
	 * Constructor
	 *
	 * @param \Rapsys\AirBundle\Entity\User $user The user
	 * @param string The token user mail
	 * @param string The access token identifier
	 * @param \DateTime The access token expires
	 * @param ?string The refresh token identifier
	 */
	public function __construct(User $user, string $mail, string $access, \DateTime $expired, ?string $refresh = null) {
		//Set defaults
		$this->user = $user;
		$this->mail = $mail;
		$this->access = $access;
		$this->refresh = $refresh;
		$this->expired = $expired;
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
		$this->googleCalendars = new ArrayCollection();
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
	 * @return GoogleToken
	 */
	public function setMail(string $mail): GoogleToken {
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
	 * Set access
	 *
	 * @param string $access
	 *
	 * @return GoogleToken
	 */
	public function setAccess(string $access): GoogleToken {
		$this->access = $access;

		return $this;
	}

	/**
	 * Get access
	 *
	 * @return string
	 */
	public function getAccess(): string {
		return $this->access;
	}

	/**
	 * Set refresh
	 *
	 * @param string $refresh
	 *
	 * @return GoogleToken
	 */
	public function setRefresh(?string $refresh): GoogleToken {
		$this->refresh = $refresh;

		return $this;
	}

	/**
	 * Get refresh
	 *
	 * @return string
	 */
	public function getRefresh(): ?string {
		return $this->refresh;
	}

	/**
	 * Set expired
	 *
	 * @param \DateTime $expired
	 *
	 * @return GoogleToken
	 */
	public function setExpired(\DateTime $expired): GoogleToken {
		$this->expired = $expired;

		return $this;
	}

	/**
	 * Get expired
	 *
	 * @return \DateTime
	 */
	public function getExpired(): \DateTime {
		return $this->expired;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return GoogleToken
	 */
	public function setCreated(\DateTime $created): GoogleToken {
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
	 * @return GoogleToken
	 */
	public function setUpdated(\DateTime $updated): GoogleToken {
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
	 * Add google calendar
	 *
	 * @param GoogleCalendar $googleCalendar
	 *
	 * @return User
	 */
	public function addGoogleCalendar(GoogleCalendar $googleCalendar): User {
		$this->googleCalendars[] = $googleCalendar;

		return $this;
	}

	/**
	 * Remove google calendar
	 *
	 * @param GoogleCalendar $googleCalendar
	 */
	public function removeGoogleCalendar(GoogleCalendar $googleCalendar): bool {
		return $this->googleCalendars->removeElement($googleCalendar);
	}

	/**
	 * Get google calendars
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getGoogleCalendars(): Collection {
		return $this->googleCalendars;
	}

	/**
	 * Set user
	 *
	 * @param \Rapsys\AirBundle\Entity\User $user
	 *
	 * @return GoogleToken
	 */
	public function setUser(User $user): GoogleToken {
		$this->user = $user;

		return $this;
	}

	/**
	 * Get user
	 *
	 * @return \Rapsys\AirBundle\Entity\User
	 */
	public function getUser(): User {
		return $this->user;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs): ?GoogleToken {
		//Check that we have an snippet instance
		if (($entity = $eventArgs->getObject()) instanceof GoogleToken) {
			//Set updated value
			return $entity->setUpdated(new \DateTime('now'));
		}
	}
}
