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

use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\GoogleCalendar;
use Rapsys\AirBundle\Entity\GoogleToken;

use Rapsys\PackBundle\Util\SluggerUtil;

class Calendar2Command extends Command {
	/**
	 * Set default name
	 */
    protected static $defaultName = 'rapsysair:calendar2';

	/**
	 * Set default description
	 */
    protected static $defaultDescription = 'Synchronize sessions in users\' calendar';

	/**
	 * Set google client scopes
	 */
	private array $scopes = [
		Calendar::CALENDAR_EVENTS,
		Calendar::CALENDAR,
		Oauth2::USERINFO_EMAIL
	];

	/**
	 * Set google client instance
	 */
	private Client $client;

	/**
	 * Set markdown instance
	 */
	private DefaultMarkdown $markdown;

	/**
	 * Set date period instance
	 */
	private \DatePeriod $period;

	/**
	 * {@inheritdoc}
	 *
	 * @param string $project The google project
	 * @param string $client The google client
	 * @param string $secret The google secret
	 */
	public function __construct(ManagerRegistry $doctrine, RouterInterface $router, SluggerUtil $slugger, TranslatorInterface $translator, string $locale, string $project, string $client, string $secret) {
		//Call parent constructor
		parent::__construct($doctrine, $router, $slugger, $translator, $locale);

		//Set google client
		$this->client = new Client(
			[
				'application_name' => $project,
				'client_id' => $client,
				'client_secret' => $secret,
				'redirect_uri' => $this->router->generate('rapsys_air_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
				'scopes' => $this->scopes,
				'access_type' => 'offline',
				#'login_hint' => $user->getMail(),
				//XXX: see https://stackoverflow.com/questions/10827920/not-receiving-google-oauth-refresh-token
				#'approval_prompt' => 'force'
				'prompt' => 'consent'
			]
		);

		//Set Markdown instance
		$this->markdown = new DefaultMarkdown;
	}

	/**
	 * Process the attribution
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		//Iterate on google tokens
		foreach($tokens = $this->doctrine->getRepository(GoogleToken::class)->findAllIndexed() as $tid => $token) {
			//Iterate on google calendars
			foreach($calendars = $token['calendars'] as $cid => $calendar) {
				//Set period
				$this->period = new \DatePeriod(
					//Start from last week
					new \DateTime('-1 week'),
					//Iterate on each day
					new \DateInterval('P1D'),
					//End with next 2 week
					new \DateTime('+2 week')
				);

				#$calendar['synchronized']
				var_dump($token);

				//TODO: see if we may be smarter here ?

				//TODO: load all calendar events here ?

				//Iterate on sessions to update
				foreach($sessions = $this->doctrine->getRepository(Session::class)->findAllByUserIdSynchronized($token['uid'], $calendar['synchronized'])) {
					//TODO: insert/update/delete events here ?
				}

				//TODO: delete remaining events here ?
			}
		}

		//TODO: get user filter ? (users_subscriptions+users_dances)

		//TODO: XXX: or fetch directly the events updated since synchronized + matching rubscriptions and/or dances

		#var_dump($tokens);
		exit;
		
		//Set sql request
		$sql =<<<SQL
SELECT
	b.*,
	GROUP_CONCAT(us.user_id) AS users
FROM (
	SELECT
		a.*,
		GROUP_CONCAT(ud.dance_id) AS dances
	FROM (
		SELECT
			t.id AS tid,
			t.mail AS gmail,
			t.user_id,
			t.access,
			t.refresh,
			t.expired,
			GROUP_CONCAT(c.id) AS cids,
			GROUP_CONCAT(c.mail) AS cmails,
			GROUP_CONCAT(c.summary) AS csummaries,
			GROUP_CONCAT(c.synchronized) AS csynchronizeds
		FROM google_tokens AS t
		JOIN google_calendars AS c ON (c.google_token_id = t.id)
		GROUP BY t.id
		ORDER BY NULL
		LIMIT 100000
	) AS a
	LEFT JOIN users_dances AS ud ON (ud.user_id = a.user_id)
	GROUP BY a.tid
	ORDER BY NULL
	LIMIT 100000
) AS b
LEFT JOIN users_subscriptions AS us ON (us.subscriber_id = b.user_id)
GROUP BY b.tid
ORDER BY NULL
SQL;
		#$sessions = $this->doctrine->getRepository(Session::class)->findAllByDanceUserModified($filter['dance'], $filter['user'], $calendar['synchronized']);
		//Iterate on google tokens
		foreach($tokens as $token) {
			//TODO: clear google client cache
			//TODO: set google token
			//Iterate on google calendars
			foreach($calendars as $calendar) {
				//Fetch sessions to sync
				$sessions = $this->doctrine->getRepository(Session::class)->findAllByDanceUserModified($filter['dance'], $filter['user'], $calendar['synchronized']);
			}
		}

		//Return success
		return self::SUCCESS;
	}
}
