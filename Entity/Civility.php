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

use Rapsys\UserBundle\Entity\Civility as BaseCivility;

class Civility extends BaseCivility {
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
	public function setShort(string $short): Civility {
		$this->short = $short;

		return $this;
	}

	/**
	 * Get short
	 *
	 * @return string
	 */
	public function getShort(): string {
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
