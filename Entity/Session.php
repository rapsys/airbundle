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
	private $start;

	/**
	 * @var \DateTime
	 */
	private $length;

	/**
	 * @var \DateTime
	 */
	private $stop;

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
	 * @var float
	 */
	private $temperature;

	/**
	 * @var float
	 */
	private $temperaturemin;

	/**
	 * @var float
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
	 * @var Application
	 */
	private $application;

	/**
	 * @var Location
	 */
	private $location;

	/**
	 * @var Slot
	 */
	private $slot;

	/**
	 * @var ArrayCollection
	 */
	private $applications;

	/**
	 * Constructor
	 */
	public function __construct() {
		//Set defaults
		$this->begin = null;
		$this->start = null;
		$this->length = null;
		$this->stop = null;
		$this->premium = null;
		$this->rainfall = null;
		$this->rainrisk = null;
		$this->realfeel = null;
		$this->realfeelmin = null;
		$this->realfeelmax = null;
		$this->temperature = null;
		$this->temperaturemin = null;
		$this->temperaturemax = null;
		$this->locked = null;
		$this->created = new \DateTime('now');
		$this->updated = new \DateTime('now');
		$this->applications = new ArrayCollection();
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
	 * Set date
	 *
	 * @param \DateTime $date
	 *
	 * @return Session
	 */
	public function setDate(\DateTime $date): Session {
		$this->date = $date;

		return $this;
	}

	/**
	 * Get date
	 *
	 * @return \DateTime
	 */
	public function getDate(): \DateTime {
		return $this->date;
	}

	/**
	 * Set begin
	 *
	 * @param \DateTime $begin
	 *
	 * @return Session
	 */
	public function setBegin(?\DateTime $begin): Session {
		$this->begin = $begin;

		return $this;
	}

	/**
	 * Get begin
	 *
	 * @return \DateTime
	 */
	public function getBegin(): ?\DateTime {
		return $this->begin;
	}

	/**
	 * Get start
	 *
	 * @return \DateTime
	 */
	public function getStart(): \DateTime {
		//With start
		if ($this->start !== null) {
			return $this->start;
		}

		//Clone date
		$this->start = clone $this->date;

		//Check if after slot
		//XXX: id=4 <=> title=After
		if ($this->slot->getId() == 4) {
			//Add one day
			$this->start->add(new \DateInterval('P1D'));
		}

		//With begin
		if ($this->begin !== null) {
			//Set start time
			$this->start->setTime(intval($this->begin->format('H')), intval($this->begin->format('i')), intval($this->begin->format('s')));
		}

		//Return start
		return $this->start;
	}

	/**
	 * Set length
	 *
	 * @param \DateTime $length
	 *
	 * @return Session
	 */
	public function setLength(?\DateTime $length): Session {
		$this->length = $length;

		return $this;
	}

	/**
	 * Get length
	 *
	 * @return \DateTime
	 */
	public function getLength(): ?\DateTime {
		return $this->length;
	}

	/**
	 * Get stop
	 *
	 * @return \DateTime
	 */
	public function getStop(): \DateTime {
		//Check start
		if ($this->stop !== null) {
			return $this->stop;
		}

		//Get start clone
		$this->stop = clone $this->getStart();

		//With length
		if ($this->length !== null) {
			//Set stop time
			$this->stop->add(new \DateInterval('PT'.$this->length->format('H').'H'.$this->length->format('i').'M'.$this->length->format('s').'S'));
		}

		//Return date
		return $this->stop;
	}

	/**
	 * Set premium
	 *
	 * @param boolean $premium
	 *
	 * @return Session
	 */
	public function setPremium(bool $premium): Session {
		$this->premium = $premium;

		return $this;
	}

	/**
	 * Get premium
	 *
	 * @return bool
	 */
	public function getPremium(): bool {
		return $this->premium;
	}

	/**
	 * Set rainfall
	 *
	 * @param float $rainfall
	 *
	 * @return Session
	 */
	public function setRainfall(?float $rainfall): Session {
		$this->rainfall = $rainfall;

		return $this;
	}

	/**
	 * Get rainfall
	 *
	 * @return float
	 */
	public function getRainfall(): ?float {
		return $this->rainfall;
	}

	/**
	 * Set rainrisk
	 *
	 * @param float $rainrisk
	 *
	 * @return Session
	 */
	public function setRainrisk(?float $rainrisk): Session {
		$this->rainrisk = $rainrisk;

		return $this;
	}

	/**
	 * Get rainrisk
	 *
	 * @return float
	 */
	public function getRainrisk(): ?float {
		return $this->rainrisk;
	}

	/**
	 * Set realfeel
	 *
	 * @param float $realfeel
	 *
	 * @return Session
	 */
	public function setRealfeel(?float $realfeel): Session {
		$this->realfeel = $realfeel;

		return $this;
	}

	/**
	 * Get realfeel
	 *
	 * @return float
	 */
	public function getRealfeel(): ?float {
		return $this->realfeel;
	}

	/**
	 * Set realfeelmin
	 *
	 * @param float $realfeelmin
	 *
	 * @return Session
	 */
	public function setRealfeelmin(?float $realfeelmin): Session {
		$this->realfeelmin = $realfeelmin;

		return $this;
	}

	/**
	 * Get realfeelmin
	 *
	 * @return float
	 */
	public function getRealfeelmin(): ?float {
		return $this->realfeelmin;
	}

	/**
	 * Set realfeelmax
	 *
	 * @param float $realfeelmax
	 *
	 * @return Session
	 */
	public function setRealfeelmax(?float $realfeelmax): Session {
		$this->realfeelmax = $realfeelmax;

		return $this;
	}

	/**
	 * Get realfeelmax
	 *
	 * @return float
	 */
	public function getRealfeelmax(): ?float {
		return $this->realfeelmax;
	}

	/**
	 * Set temperature
	 *
	 * @param float $temperature
	 *
	 * @return Session
	 */
	public function setTemperature(?float $temperature): Session {
		$this->temperature = $temperature;

		return $this;
	}

	/**
	 * Get temperature
	 *
	 * @return float
	 */
	public function getTemperature(): ?float {
		return $this->temperature;
	}

	/**
	 * Set temperaturemin
	 *
	 * @param float $temperaturemin
	 *
	 * @return Session
	 */
	public function setTemperaturemin(?float $temperaturemin): Session {
		$this->temperaturemin = $temperaturemin;

		return $this;
	}

	/**
	 * Get temperaturemin
	 *
	 * @return float
	 */
	public function getTemperaturemin(): ?float {
		return $this->temperaturemin;
	}

	/**
	 * Set temperaturemax
	 *
	 * @param float $temperaturemax
	 *
	 * @return Session
	 */
	public function setTemperaturemax(?float $temperaturemax): Session {
		$this->temperaturemax = $temperaturemax;

		return $this;
	}

	/**
	 * Get temperaturemax
	 *
	 * @return float
	 */
	public function getTemperaturemax(): ?float {
		return $this->temperaturemax;
	}

	/**
	 * Set locked
	 *
	 * @param \DateTime $locked
	 *
	 * @return Session
	 */
	public function setLocked(?\DateTime $locked): Session {
		$this->locked = $locked;

		return $this;
	}

	/**
	 * Get locked
	 *
	 * @return \DateTime
	 */
	public function getLocked(): ?\DateTime {
		return $this->locked;
	}

	/**
	 * Set created
	 *
	 * @param \DateTime $created
	 *
	 * @return Session
	 */
	public function setCreated(\DateTime $created): Session {
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
	 * @return Session
	 */
	public function setUpdated(\DateTime $updated): Session {
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
	 * @return Session
	 */
	public function addApplication(Application $application): Session {
		$this->applications[] = $application;

		return $this;
	}

	/**
	 * Remove application
	 *
	 * @param Application $application
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
	 * Set location
	 *
	 * @param Location $location
	 *
	 * @return Session
	 */
	public function setLocation(Location $location): Session {
		$this->location = $location;

		return $this;
	}

	/**
	 * Get location
	 *
	 * @return Location
	 */
	public function getLocation(): Location {
		return $this->location;
	}

	/**
	 * Set slot
	 *
	 * @param Slot $slot
	 *
	 * @return Session
	 */
	public function setSlot(Slot $slot): Session {
		$this->slot = $slot;

		return $this;
	}

	/**
	 * Get slot
	 *
	 * @return Slot
	 */
	public function getSlot(): Slot {
		return $this->slot;
	}

	/**
	 * Set application
	 *
	 * @param Application $application
	 *
	 * @return Session
	 */
	public function setApplication(Application $application): Session {
		$this->application = $application;

		return $this;
	}

	/**
	 * Get application
	 *
	 * @return Application
	 */
	public function getApplication(): ?Application {
		return $this->application;
	}

	/**
	 * {@inheritdoc}
	 */
	public function preUpdate(PreUpdateEventArgs $eventArgs) {
		//Check that we have a session instance
		if (($session = $eventArgs->getEntity()) instanceof Session) {
			//Set updated value
			$session->setUpdated(new \DateTime('now'));
		}
	}

	/**
	 * Wether if session is a premium day
	 *
	 * Consider as premium a day off for afternoon, the eve for evening and after
	 * Store computed result in premium member for afternoon and evening
	 *
	 * @return bool Whether the date is day off or not
	 */
	public function isPremium(): bool {
		//Without date
		if (empty($date = $this->date)) {
			throw new \LogicException('Property date is empty');
		}

		//Without slot
		if (empty($slot = $this->slot) || empty($slotTitle = $slot->getTitle())) {
			throw new \LogicException('Property slot is empty');
		}

		//With evening and after slot
		if ($slotTitle == 'Evening' || $slotTitle == 'After') {
			//Evening and after session is considered premium when the eve is a day off
			$date = (clone $date)->add(new \DateInterval('P1D'));
		}

		//Get day number
		$w = $date->format('w');

		//Check if weekend day
		if ($w == 0 || $w == 6) {
			//With afternoon and evening slot
			if ($slotTitle == 'Afternoon' || $slotTitle == 'Evening') {
				//Save premium
				$this->premium = true;
			}

			//Date is weekend day
			return true;
		}

		//Get date day
		$d = $date->format('d');

		//Get date month
		$m = $date->format('m');

		//Check if fixed holiday
		if (
			//Check if 1st january
			($d == 1 && $m == 1) ||
			//Check if 1st may
			($d == 1 && $m == 5) ||
			//Check if 8st may
			($d == 8 && $m == 5) ||
			//Check if 14st july
			($d == 14 && $m == 7) ||
			//Check if 15st august
			($d == 15 && $m == 8) ||
			//Check if 1st november
			($d == 1 && $m == 11) ||
			//Check if 11st november
			($d == 11 && $m == 11) ||
			//Check if 25st december
			($d == 25 && $m == 12)
		) {
			//With afternoon and evening slot
			if ($slotTitle == 'Afternoon' || $slotTitle == 'Evening') {
				//Save premium
				$this->premium = true;
			}

			//Date is a fixed holiday
			return true;
		}

		//Get eastern
		$eastern = $this->getEastern($date->format('Y'));

		//Check dynamic holidays
		if (
			(clone $eastern)->add(new \DateInterval('P1D')) == $date ||
			(clone $eastern)->add(new \DateInterval('P39D')) == $date ||
			(clone $eastern)->add(new \DateInterval('P50D')) == $date
		) {
			//With afternoon and evening slot
			if ($slotTitle == 'Afternoon' || $slotTitle == 'Evening') {
				//Save premium
				$this->premium = true;
			}

			//Date is a dynamic holiday
			return true;
		}

		//With afternoon and evening slot
		if ($slotTitle == 'Afternoon' || $slotTitle == 'Evening') {
			//Save premium
			$this->premium = false;
		}

		//Date is not a holiday and week day
		return false;
	}

	/**
	 * Compute eastern for selected year
	 *
	 * @param string $year The eastern year
	 *
	 * @return DateTime The eastern date
	 */
	private function getEastern(string $year): \DateTime {
		//Set static
		static $data = null;

		//Check if already computed
		if (isset($data[$year])) {
			//Return computed eastern
			return $data[$year];
		//Check if data is null
		} elseif (is_null($data)) {
			//Init data array
			$data = [];
		}

		$d = (19 * ($year % 19) + 24) % 30;

		$e = (2 * ($year % 4) + 4 * ($year % 7) + 6 * $d + 5) % 7;

		$day = 22 + $d + $e;

		$month = 3;

		if ($day > 31) {
			$day = $d + $e - 9;
			$month = 4;
		} elseif ($d == 29 && $e == 6) {
			$day = 10;
			$month = 4;
		} elseif ($d == 28 && $e == 6) {
			$day = 18;
			$month = 4;
		}

		//Store eastern in data
		return ($data[$year] = new \DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day)));
	}
}
