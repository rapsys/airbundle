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

/**
 * {@inheritdoc}
 */
class Command extends BaseCommand {
	/**
	 * {@inheritdoc}
	 *
	 * Creates new command
	 *
	 * @param ManagerRegistry $doctrine The doctrine instance
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The slugger instance
	 * @param TranslatorInterface $translator The translator instance
	 * @param string $locale The default locale
	 */
	public function __construct(protected ManagerRegistry $doctrine, protected string $locale, protected RouterInterface $router, protected SluggerUtil $slugger, protected TranslatorInterface $translator, protected ?string $name = null) {
		//Fix name
		$this->name = $this->name ?? static::getName();

		//Call parent constructor
		parent::__construct($this->name);

		//With description
		if (!empty($this->description)) {
			//Set description
			$this->setDescription($this->description);
		}

		//With help
		if (!empty($this->help)) {
			//Set help
			$this->setHelp($this->help);
		}

		//Get router context
		$context = $this->router->getContext();

		//Set hostname
		$context->setHost($_ENV['RAPSYSAIR_HOSTNAME']);

		//Set scheme
		$context->setScheme($_ENV['RAPSYSAIR_SCHEME'] ?? 'https');
	}

	/**
	 * {@inheritdoc}
	 *
	 * Return the command name
	 */
	public function getName(): string {
		//With namespace
		if ($npos = strrpos(static::class, '\\')) {
			//Set name pos
			$npos++;
		//Without namespace
		} else {
			$npos = 0;
		}

		//With trailing command
		if (substr(static::class, -strlen('Command'), strlen('Command')) === 'Command') {
			//Set bundle pos
			$bpos = strlen(static::class) - $npos - strlen('Command');
		//Without bundle
		} else {
			//Set bundle pos
			$bpos = strlen(static::class) - $npos;
		}

		//Return command alias
		return RapsysAirBundle::getAlias().':'.strtolower(substr(static::class, $npos, $bpos));
	}
}
