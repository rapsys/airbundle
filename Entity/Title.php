<?php

namespace Rapsys\AirBundle\Entity;

class Title extends \Rapsys\UserBundle\Entity\Title {
	/**
	 * @var string
	 */
	private $short;

	/**
	 * Set short
	 *
	 * @param string $short
	 *
	 * @return Title
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
	 * Returns a string representation of the title
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->short;
	}
}
