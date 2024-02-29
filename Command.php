<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\AirBundle\RapsysAirBundle;

use Rapsys\PackBundle\Util\SluggerUtil;

class Command extends BaseCommand {
	/**
	 * Creates new command
	 *
	 * @param ManagerRegistry $doctrine The doctrine instance
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The slugger instance
	 * @param TranslatorInterface $translator The translator instance
	 * @param string $locale The default locale
	 */
	public function __construct(protected ManagerRegistry $doctrine, protected RouterInterface $router, protected SluggerUtil $slugger, protected TranslatorInterface $translator, protected string $locale) {
		//Call parent constructor
		parent::__construct();

		//Get router context
		$context = $this->router->getContext();

		//Set host
		//TODO: set it in env RAPSYSAIR_HOST ?
		$context->setHost('airlibre.eu');

		//Set scheme
		//TODO: set it in env RAPSYSAIR_SCHEME ?
		$context->setScheme('https');

		//With default name
		//TODO: XXX: see how to make it works
		/*if (isset(self::$defaultName)) {
			$this->name = self::$defaultName;
		}

		//With default description
		if (isset(self::$defaultDescription)) {
			$this->name = self::$defaultDescription;
		}*/
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias(): string {
		return RapsysAirBundle::getAlias();
	}
}
