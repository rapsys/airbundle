<?php

namespace Rapsys\AirBundle\Entity;

/**
 * Session
 */
class Session {
	/**
	 * @var integer
	 */
	private $id;

	/**
	 * @var \DateTime
	 */
	private $date;

	/**
	 * @var \DateTime
	 */
	private $begin;

	/**
	 * @var \DateTime
	 */
	private $end;

	/**
	 * @var \DateTime
	 */
	private $created;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $applications;

	/**
	 * @var \Rapsys\AirBundle\Entity\Location
	 */
	private $location;

	/**
	 * @var \Rapsys\AirBundle\Entity\Application
	 */
	private $application;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->applications = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set date
	 *
	 * @param \DateTime $date
	 *
	 * @return Session
	 */
	public function setDate($date) {
		$this->date = $date;

		return $this;
	}

	/**
	 * Get date
	 *
	 * @return \DateTime
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * Set begin
	 *
	 * @param \DateTime $begin
	 *
	 * @return Session
	 */
	public function setBegin($begin) {
		$this->begin = $begin;

		return $this;
	}

	/**
	 * Get begin
	 *
	 * @return \DateTime
	 */
	public function getBegin() {
		return $this->begin;
	}

	/**
	 * Set end
	 *
	 * @param \DateTime $end
	 *
	 * @return Session
	 */
	public function setEnd($end) {
		$this->end = $end;

		return $this;
	}

	/**
	 * Get end
	 *
	 * @return \DateTime
	 */
	public function getEnd() {
		return $this->end;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Session
	 */
	public function setCreated($created) {
		$this->created = $created;

		return $this;
	}

	/**
	 * Get created
	 *
	 * @return \DateTime
	 */
	public function getCreated() {
		return $this->created;
	}

	/**
	 * Set updated
	 *
	 * @param \DateTime $updated
	 *
	 * @return Session
	 */
	public function setUpdated($updated) {
		$this->updated = $updated;

		return $this;
	}

	/**
	 * Get updated
	 *
	 * @return \DateTime
	 */
	public function getUpdated() {
		return $this->updated;
	}

	/**
	 * Add application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 *
	 * @return Session
	 */
	public function addApplication(\Rapsys\AirBundle\Entity\Application $application) {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 */
	public function removeApplication(\Rapsys\AirBundle\Entity\Application $application) {
		$this->applications->removeElement($application);
	}

	/**
	 * Get applications
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getApplications() {
		return $this->applications;
	}

	/**
	 * Set location
	 *
	 * @param \Rapsys\AirBundle\Entity\Location $location
	 *
	 * @return Session
	 */
	public function setLocation(\Rapsys\AirBundle\Entity\Location $location = null) {
		$this->location = $location;

		return $this;
	}

	/**
	 * Get location
	 *
	 * @return \Rapsys\AirBundle\Entity\Location
	 */
	public function getLocation() {
		return $this->location;
	}
	/**
	 * @var \Rapsys\AirBundle\Entity\Slot
	 */
	private $slot;


	/**
	 * Set slot
	 *
	 * @param \Rapsys\AirBundle\Entity\Slot $slot
	 *
	 * @return Session
	 */
	public function setSlot(\Rapsys\AirBundle\Entity\Slot $slot = null) {
		$this->slot = $slot;

		return $this;
	}

	/**
	 * Get slot
	 *
	 * @return \Rapsys\AirBundle\Entity\Slot
	 */
	public function getSlot() {
		return $this->slot;
	}

	/**
	 * Set application
	 *
	 * @param \Rapsys\AirBundle\Entity\Application $application
	 *
	 * @return Session
	 */
	public function setApplication(\Rapsys\AirBundle\Entity\Application $application = null) {
		$this->application = $application;

		return $this;
	}

	/**
	 * Get application
	 *
	 * @return \Rapsys\AirBundle\Entity\Application
	 */
	public function getApplication() {
		return $this->application;
	}
}
