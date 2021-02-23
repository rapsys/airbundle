<?php

namespace Rapsys\AirBundle\Entity;

class Civility extends \Rapsys\UserBundle\Entity\Civility {
	/**
	 * @var string
	 */
	private $short;

	/**
	 * Set short
	 *
	 * @param string $short
	 *
	 * @return Civility
	 */
	public function setShort($short) {
		$this->short = $short;

		return $this;
	}

	/**
	 * Get short
	 *
	 * @return string
	 */
	public function getShort() {
		return $this->short;
	}

	/**
	 * Returns a string representation of the civility
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->short;
	}
}
