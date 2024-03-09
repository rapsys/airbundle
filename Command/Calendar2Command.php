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

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Oauth2;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Twig\Extra\Markdown\DefaultMarkdown;

use Rapsys\AirBundle\Command;
use Rapsys\AirBundle\Entity\GoogleCalendar;
use Rapsys\AirBundle\Entity\GoogleToken;
use Rapsys\AirBundle\Entity\Session;

use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * {@inheritdoc}
 *
 * Synchronize sessions in users' calendar
 */
class Calendar2Command extends Command {
	/**
	 * Set description
	 *
	 * Shown with bin/console list
	 */
	protected string $description = 'Synchronize sessions in users\' calendar';

	/**
	 * Set help
	 *
	 * Shown with bin/console --help rapsysair:calendar2
	 */
	protected string $help = 'This command synchronize sessions in users\' google calendar';

	/**
	 * {@inheritdoc}
	 */
	public function __construct(protected ManagerRegistry $doctrine, protected string $locale, protected RouterInterface $router, protected SluggerUtil $slugger, protected TranslatorInterface $translator, protected Client $google, protected DefaultMarkdown $markdown) {
		//Call parent constructor
		parent::__construct($this->doctrine, $this->locale, $this->router, $this->slugger, $this->translator);

		//Replace google client redirect uri
		$this->google->setRedirectUri($this->router->generate($this->google->getRedirectUri(), [], UrlGeneratorInterface::ABSOLUTE_URL));
	}

	/**
	 * Process the attribution
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		//Set period
		$period = new \DatePeriod(
			//Start from last week
			new \DateTime('-1 week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next 2 week
			new \DateTime('+2 week')
		);

		//Iterate on google tokens
		foreach($tokens = $this->doctrine->getRepository(GoogleToken::class)->findAllIndexed() as $tid => $token) {
			//Iterate on google calendars
			foreach($calendars = $token['calendars'] as $cid => $calendar) {
				#$calendar['synchronized']
				var_dump($token);

				//TODO: see if we may be smarter here ?

				//TODO: load all calendar events here ?

				//Iterate on sessions to update
				foreach($sessions = $this->doctrine->getRepository(Session::class)->findAllByUserIdSynchronized($token['uid'], $calendar['synchronized']) as $session) {
					//TODO: insert/update/delete events here ?
				}

				//TODO: delete remaining events here ?
			}
		}

		//TODO: get user filter ? (users_subscriptions+users_dances)

		//TODO: XXX: or fetch directly the events updated since synchronized + matching rubscriptions and/or dances

		exit;

		//Return success
		return self::SUCCESS;
	}
}
