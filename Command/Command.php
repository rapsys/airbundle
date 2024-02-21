<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Rapsys\AirBundle\RapsysAirBundle;

use Rapsys\PackBundle\Util\SluggerUtil;

class Command extends BaseCommand {
	/**
	 * Doctrine instance
	 *
	 * @var ManagerRegistry
	 */
	protected ManagerRegistry $doctrine;

	/**
	 * Router instance
	 *
	 * @var RouterInterface
	 */
	protected RouterInterface $router;

	/**
	 * Slugger instance
	 *
	 * @var SluggerUtil
	 */
	protected SluggerUtil $slugger;

	/**
	 * Translator instance
	 *
	 * @var TranslatorInterface
	 */
	protected TranslatorInterface $translator;

	/**
	 * Locale
	 *
	 * @var string
	 */
	protected string $locale;

	/**
	 * Creates new command
	 *
	 * @param ManagerRegistry $doctrine The doctrine instance
	 * @param RouterInterface $router The router instance
	 * @param SluggerUtil $slugger The slugger instance
	 * @param TranslatorInterface $translator The translator instance
	 * @param string $locale The default locale
	 */
	public function __construct(ManagerRegistry $doctrine, RouterInterface $router, SluggerUtil $slugger, TranslatorInterface $translator, string $locale) {
		//Call parent constructor
		parent::__construct();

		//Set doctrine
		$this->doctrine = $doctrine;

		//Set router
		$this->router = $router;

		//Set slugger
		$this->slugger = $slugger;

		//Get router context
		$context = $this->router->getContext();

		//Set host
		$context->setHost('airlibre.eu');

		//Set scheme
		$context->setScheme('https');

		//Set the translator
		$this->translator = $translator;

		//Set locale
		$this->locale = $locale;
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
