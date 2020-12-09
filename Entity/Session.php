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
	private $start = null;

	/**
	 * @var \DateTime
	 */
	private $length;

	/**
	 * @var \DateTime
	 */
	private $stop = null;

	/**
	 * @var boolean
	 */
	private $premium;

	/**
	 * @var float
	 */
	private $rainfall;

	/**
	 * @var float
	 */
	private $rainrisk;

	/**
	 * @var float
	 */
	private $realfeel;

	/**
	 * @var float
	 */
	private $realfeelmin;

	/**
	 * @var float
	 */
	private $realfeelmax;

	/**
	 * @var integer
	 */
	private $temperature;

	/**
	 * @var integer
	 */
	private $temperaturemin;

	/**
	 * @var integer
	 */
	private $temperaturemax;

	/**
	 * @var \DateTime
	 */
	private $locked;

	/**
	 * @var \DateTime
	 */
	private $created;

	/**
	 * @var \DateTime
	 */
	private $updated;

	/**
	 * @var \Rapsys\AirBundle\Entity\Application
	 */
	private $application;

	/**
	 * @var \Rapsys\AirBundle\Entity\Location
	 */
	private $location;

	/**
	 * @var \Rapsys\AirBundle\Entity\Slot
	 */
	private $slot;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 */
	private $applications;

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
	 * Get start
	 *
	 * @return \DateTime
	 */
	public function getStart() {
		//Check start
		if ($this->start !== null) {
			return $this->start;
		}

		//Clone date
		$this->start = clone $this->date;

		//Check if after slot
		if ($this->slot->getTitle() == 'After') {
			//Add one day
			$this->start->add(new \DateInterval('P1D'));
		}

		//Return date
		return $this->start->setTime($this->begin->format('H'), $this->begin->format('i'), $this->begin->format('s'));
	}

	/**
	 * Set length
	 *
	 * @param \DateTime $length
	 *
	 * @return Session
	 */
	public function setLength($length) {
		$this->length = $length;

		return $this;
	}

	/**
	 * Get length
	 *
	 * @return \DateTime
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Get stop
	 *
	 * @return \DateTime
	 */
	public function getStop() {
		//Check start
		if ($this->stop !== null) {
			return $this->stop;
		}

		//Get start clone
		$this->stop = clone $this->getStart();

		//Return date
		return $this->stop->add(new \DateInterval('PT'.$this->length->format('H').'H'.$this->length->format('i').'M'.$this->length->format('s').'S'));
	}

	/**
	 * Set premium
	 *
	 * @param boolean $premium
	 *
	 * @return Session
	 */
	public function setPremium($premium) {
		$this->premium = $premium;

		return $this;
	}

	/**
	 * Get premium
	 *
	 * @return boolean
	 */
	public function getPremium() {
		return $this->premium;
	}

	/**
	 * Set rainfall
	 *
	 * @param boolean $rainfall
	 *
	 * @return Session
	 */
	public function setRainfall($rainfall) {
		$this->rainfall = $rainfall;

		return $this;
	}

	/**
	 * Get rainfall
	 *
	 * @return boolean
	 */
	public function getRainfall() {
		return $this->rainfall;
	}

	/**
	 * Set rainrisk
	 *
	 * @param boolean $rainrisk
	 *
	 * @return Session
	 */
	public function setRainrisk($rainrisk) {
		$this->rainrisk = $rainrisk;

		return $this;
	}

	/**
	 * Get rainrisk
	 *
	 * @return boolean
	 */
	public function getRainrisk() {
		return $this->rainrisk;
	}

	/**
	 * Set realfeel
	 *
	 * @param integer $realfeel
	 *
	 * @return Session
	 */
	public function setRealfeel($realfeel) {
		$this->realfeel = $realfeel;

		return $this;
	}

	/**
	 * Get realfeel
	 *
	 * @return integer
	 */
	public function getRealfeel() {
		return $this->realfeel;
	}

	/**
	 * Set realfeelmin
	 *
	 * @param integer $realfeelmin
	 *
	 * @return Session
	 */
	public function setRealfeelmin($realfeelmin) {
		$this->realfeelmin = $realfeelmin;

		return $this;
	}

	/**
	 * Get realfeelmin
	 *
	 * @return integer
	 */
	public function getRealfeelmin() {
		return $this->realfeelmin;
	}

	/**
	 * Set realfeelmax
	 *
	 * @param integer $realfeelmax
	 *
	 * @return Session
	 */
	public function setRealfeelmax($realfeelmax) {
		$this->realfeelmax = $realfeelmax;

		return $this;
	}

	/**
	 * Get realfeelmax
	 *
	 * @return integer
	 */
	public function getRealfeelmax() {
		return $this->realfeelmax;
	}

	/**
	 * Set temperature
	 *
	 * @param integer $temperature
	 *
	 * @return Session
	 */
	public function setTemperature($temperature) {
		$this->temperature = $temperature;

		return $this;
	}

	/**
	 * Get temperature
	 *
	 * @return integer
	 */
	public function getTemperature() {
		return $this->temperature;
	}

	/**
	 * Set temperaturemin
	 *
	 * @param integer $temperaturemin
	 *
	 * @return Session
	 */
	public function setTemperaturemin($temperaturemin) {
		$this->temperaturemin = $temperaturemin;

		return $this;
	}

	/**
	 * Get temperaturemin
	 *
	 * @return integer
	 */
	public function getTemperaturemin() {
		return $this->temperaturemin;
	}

	/**
	 * Set temperaturemax
	 *
	 * @param integer $temperaturemax
	 *
	 * @return Session
	 */
	public function setTemperaturemax($temperaturemax) {
		$this->temperaturemax = $temperaturemax;

		return $this;
	}

	/**
	 * Get temperaturemax
	 *
	 * @return integer
	 */
	public function getTemperaturemax() {
		return $this->temperaturemax;
	}

	/**
	 * Set locked
	 *
	 * @param \DateTime $locked
	 *
	 * @return Session
	 */
	public function setLocked($locked) {
		$this->locked = $locked;

		return $this;
	}

	/**
	 * Get locked
	 *
	 * @return \DateTime
	 */
	public function getLocked() {
		return $this->locked;
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
