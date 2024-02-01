<?php

namespace Rapsys\AirBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Extra\Markdown\DefaultMarkdown;

use Rapsys\AirBundle\Entity\Session;

class CalendarCommand extends Command {
	//Set failure constant
	const FAILURE = 1;

	///Set success constant
	const SUCCESS = 0;

	///Config array
	protected $config;

	/**
	 * Doctrine instance
	 *
	 * @var ManagerRegistry
	 */
	protected $doctrine;

	///Locale
	protected $locale;

	///Translator instance
	protected $translator;

	/**
	 * Inject doctrine, container and translator interface
	 *
	 * @param ContainerInterface $container The container instance
	 * @param ManagerRegistry $doctrine The doctrine instance
	 * @param RouterInterface $router The router instance
	 * @param TranslatorInterface $translator The translator instance
	 */
    public function __construct(ContainerInterface $container, ManagerRegistry $doctrine, RouterInterface $router, TranslatorInterface $translator) {
		//Call parent constructor
		parent::__construct();

		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Retrieve locale
		$this->locale = $container->getParameter('kernel.default_locale');

		//Store doctrine
        $this->doctrine = $doctrine;

		//Store router
        $this->router = $router;

		//Get router context
		$context = $this->router->getContext();

		//Set host
		$context->setHost('airlibre.eu');

		//Set scheme
		$context->setScheme('https');

		//Set the translator
		$this->translator = $translator;
	}

	///Configure attribute command
	protected function configure() {
		//Configure the class
		$this
			//Set name
			->setName('rapsysair:calendar')
			//Set description shown with bin/console list
			->setDescription('Synchronize sessions in calendar')
			//Set description shown with bin/console --help airlibre:attribute
			->setHelp('This command synchronize sessions in google calendar');
	}

	///Process the attribution
	protected function execute(InputInterface $input, OutputInterface $output) {
		//Compute period
		$period = new \DatePeriod(
			//Start from last week
			new \DateTime('-1 week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next 2 week
			new \DateTime('+2 week')
		);

		//Retrieve events to update
		$sessions = $this->doctrine->getRepository(Session::class)->fetchAllByDatePeriod($period, $this->locale);

		//Markdown converted instance
		$markdown = new DefaultMarkdown;

		//Retrieve cache object
		//XXX: by default stored in /tmp/symfony-cache/@/W/3/6SEhFfeIW4UMDlAII+Dg
		//XXX: stored in %kernel.project_dir%/var/cache/airlibre/0/P/IA20X0K4dkMd9-+Ohp9Q
		$cache = new FilesystemAdapter($this->config['cache']['namespace'], $this->config['cache']['lifetime'], $this->config['path']['cache']);

		//Retrieve calendars
		$cacheCalendars = $cache->getItem('calendars');

		//Without calendars
		if (!$cacheCalendars->isHit()) {
			//Return failure
			return self::FAILURE;
		}

		//Retrieve calendars
		$calendars = $cacheCalendars->get();

		//XXX: calendars content
		#var_export($calendars);

		//Check expired token
		foreach($calendars as $clientId => $client) {
			//Get google client
			$googleClient = new \Google\Client(['application_name' => $client['project'], 'client_id' => $clientId, 'client_secret' => $client['secret'], 'redirect_uri' => $client['redirect']]);

			//Iterate on each tokens
			foreach($client['tokens'] as $tokenId => $token) {
				//Set token
				$googleClient->setAccessToken(
					[
						'access_token' => $tokenId,
						'refresh_token' => $token['refresh'],
						'expires_in' => $token['expire'],
						'scope' => $token['scope'],
						'token_type' => $token['type'],
						'created' => $token['created']
					]
				);

				//With expired token
				if ($exp = $googleClient->isAccessTokenExpired()) {
					//Refresh token
					if (($refreshToken = $googleClient->getRefreshToken()) && ($googleToken = $googleClient->fetchAccessTokenWithRefreshToken($refreshToken)) && empty($googleToken['error'])) {
						//Add refreshed token
						$calendars[$clientId]['tokens'][$googleToken['access_token']] = [
							'calendar' => $token['calendar'],
							'prefix' => $token['prefix'],
							'refresh' => $googleToken['refresh_token'],
							'expire' => $googleToken['expires_in'],
							'scope' => $googleToken['scope'],
							'type' => $googleToken['token_type'],
							'created' => $googleToken['created']
						];

						//Remove old token
						unset($calendars[$clientId]['tokens'][$tokenId]);
					} else {
						//Drop token
						unset($calendars[$clientId]['tokens'][$tokenId]);

						//Without tokens
						if (empty($calendars[$clientId]['tokens'])) {
							//Drop client
							unset($calendars[$clientId]);
						}

						//Save calendars
						$cacheCalendars->set($calendars);

						//Save calendar
						$cache->save($cacheCalendars);

						//Drop token and report
						echo 'Token '.$tokenId.' for calendar '.$token['calendar'].' has expired and is not refreshable'."\n";

						//Return failure
						//XXX: we want that mail and stop here
						return self::FAILURE;
					}
				}
			}
		}

		//Save calendars
		$cacheCalendars->set($calendars);

		//Save calendar
		$cache->save($cacheCalendars);

		//Iterate on each calendar client
		foreach($calendars as $clientId => $client) {
			//Get google client
			$googleClient = new \Google\Client(['application_name' => $client['project'], 'client_id' => $clientId, 'client_secret' => $client['secret'], 'redirect_uri' => $client['redirect']]);

			//Iterate on each tokens
			foreach($client['tokens'] as $tokenId => $token) {
				//Set token
				$googleClient->setAccessToken(
					[
						'access_token' => $tokenId,
						'refresh_token' => $token['refresh'],
						'expires_in' => $token['expire'],
						'scope' => $token['scope'],
						'token_type' => $token['type'],
						'created' => $token['created']
					]
				);

				//With expired token
				if ($exp = $googleClient->isAccessTokenExpired()) {
					//Last chance to skip this run
					continue;
				}

				//Get google calendar
				$googleCalendar = new \Google\Service\Calendar($googleClient);

				//Retrieve calendar
				try {
					$calendar = $googleCalendar->calendars->get($token['calendar']);
				//Catch exception
				} catch(\Google\Service\Exception $e) {
					//Display exception
					//TODO: handle codes here https://developers.google.com/calendar/api/guides/errors
					echo 'Exception '.$e->getCode().':'.$e->getMessage().' in '.$e->getFile().' +'.$e->getLine()."\n";
					echo $e->getTraceAsString()."\n";

					//Return failure
					return self::FAILURE;
				}

				//Init events
				$events = [];

				//Set filters
				$filters = [
					//XXX: show even deleted event to be able to update them
					'showDeleted' => true,
					//TODO: fetch events one day before and one day after to avoid triggering double insert duplicate key 409 errors :=) on google
					'timeMin' => $period->getStartDate()->format(\DateTime::ISO8601),
					'timeMax' => $period->getEndDate()->format(\DateTime::ISO8601)
					/*, 'iCalUID' => 'airlibre/?????'*//*'orderBy' => 'startTime', */
				];

				//Retrieve event collection
				$googleEvents = $googleCalendar->events->listEvents($token['calendar'], $filters);

				//Iterate until reached end
				while (true) {
					//Iterate on each event
					foreach ($googleEvents->getItems() as $event) {
						//Store event by id
						if (preg_match('/^'.$token['prefix'].'([0-9]+)$/', $id = $event->getId(), $matches)) {
							$events[$matches[1]] = $event;
						//XXX: 3rd party events with id not matching prefix are skipped
						#} else {
						#	echo 'Skipping '.$event->getId().':'.$event->getSummary()."\n";*/
						}
					}

					//Get page token
					$pageToken = $googleEvents->getNextPageToken();

					//Handle next page
					if ($pageToken) {
						//Replace collection with next one
						$googleEvents = $service->events->listEvents($token['calendar'], $filters+['pageToken' => $pageToken]);
					} else {
						break;
					}
				}

				//Iterate on each session to sync
				foreach($sessions as $sessionId => $session) {
					//Init shared properties
					//TODO: validate for constraints here ??? https://developers.google.com/calendar/api/guides/extended-properties
					$shared = [
						'gps' => $session['l_latitude'].','.$session['l_longitude']
					];

					//Init source
					$source = [
						'title' => $this->translator->trans('Session %id% by %pseudonym%', ['%id%' => $sessionId, '%pseudonym%' => $session['au_pseudonym']]).' '.$this->translator->trans('at '.$session['l_title']),
						'url' => $this->router->generate('rapsys_air_session_view', ['id' => $sessionId], UrlGeneratorInterface::ABSOLUTE_URL)
					];

					//Init description
					$description = 'Description :'."\n".strip_tags(preg_replace('!<a href="([^"]+)"(?: title="[^"]+")?'.'>([^<]+)</a>!', '\1', $markdown->convert(strip_tags($session['p_description']))));
					$shared['description'] = $markdown->convert(strip_tags($session['p_description']));

					//Add class when available
					if (!empty($session['p_class'])) {
						$shared['class'] = $session['p_class'];
						$description .= "\n\n".'Classe :'."\n".$session['p_class'];
					}

					//Add contact when available
					if (!empty($session['p_contact'])) {
						$shared['contact'] = $session['p_contact'];
						$description .= "\n\n".'Contact :'."\n".$session['p_contact'];
					}

					//Add donate when available
					if (!empty($session['p_donate'])) {
						$shared['donate'] = $session['p_donate'];
						$description .= "\n\n".'Contribuer :'."\n".$session['p_donate'];
					}

					//Add link when available
					if (!empty($session['p_link'])) {
						$shared['link'] = $session['p_link'];
						$description .= "\n\n".'Site :'."\n".$session['p_link'];
					}

					//Add profile when available
					if (!empty($session['p_profile'])) {
						$shared['profile'] = $session['p_profile'];
						$description .= "\n\n".'Réseau social :'."\n".$session['p_profile'];
					}

					//Locked session
					if (!empty($session['locked']) && $events[$sessionId]) {
						//With events
						if (!empty($event = $events[$sessionId])) {
							try {
								//Delete the event
								$googleCalendar->events->delete($token['calendar'], $event->getId());
							//Catch exception
							} catch(\Google\Service\Exception $e) {
								//Display exception
								//TODO: handle codes here https://developers.google.com/calendar/api/guides/errors
								echo 'Exception '.$e->getCode().':'.$e->getMessage().' in '.$e->getFile().' +'.$e->getLine()."\n";
								echo $e->getTraceAsString()."\n";

								//Return failure
								return self::FAILURE;
							}
						}
					//Without event
					} elseif (empty($events[$sessionId])) {
						//Init event
						$event = new \Google\Service\Calendar\Event(
							[
								//TODO: replace 'airlibre' with $this->config['calendar']['prefix'] when possible with prefix validating [a-v0-9]{5,}
								//XXX: see https://developers.google.com/calendar/api/v3/reference/events/insert#id
								'id' => $token['prefix'].$sessionId,
								'summary' => $session['au_pseudonym'].' '.$this->translator->trans('at '.$session['l_short']),
								#'description' => $markdown->convert(strip_tags($session['p_description'])),
								'description' => $description,
								'status' => empty($session['a_canceled'])?'confirmed':'cancelled',
								'location' => implode(' ', [$session['l_address'], $session['l_zipcode'], $session['l_city']]),
								'source' => $source,
								'extendedProperties' => [
									'shared' => $shared
								],
								//TODO: colorId ?
								//TODO: attendees[] ?
								'start' => [
									'dateTime' => $session['start']->format(\DateTime::ISO8601)
								],
								'end' => [
									'dateTime' => $session['stop']->format(\DateTime::ISO8601)
								]
							]
						);

						try {
							//Insert the event
							$googleCalendar->events->insert($token['calendar'], $event);
						//Catch exception
						} catch(\Google\Service\Exception $e) {
							//Display exception
							//TODO: handle codes here https://developers.google.com/calendar/api/guides/errors
							echo 'Exception '.$e->getCode().':'.$e->getMessage().' in '.$e->getFile().' +'.$e->getLine()."\n";
							echo $e->getTraceAsString()."\n";

							//Return failure
							return self::FAILURE;
						}
					// With event
					} else {
						//Set event
						$event = $events[$sessionId];

						//With updated event
						if ($session['updated'] >= (new \DateTime($event->getUpdated()))) {
							//Set summary
							$event->setSummary($session['au_pseudonym'].' '.$this->translator->trans('at '.$session['l_short']));

							//Set description
							$event->setDescription($description);

							//Set status
							$event->setStatus(empty($session['a_canceled'])?'confirmed':'cancelled');

							//Set location
							$event->setLocation(implode(' ', [$session['l_address'], $session['l_zipcode'], $session['l_city']]));

							//Get source
							$eventSource = $event->getSource();

							//Update source title
							$eventSource->setTitle($source['title']);

							//Update source url
							$eventSource->setUrl($source['url']);

							//Set source
							#$event->setSource($source);

							//Get extended properties
							$extendedProperties = $event->getExtendedProperties();

							//Update shared
							$extendedProperties->setShared($shared);

							//TODO: colorId ?
							//TODO: attendees[] ?

							//Set start
							$start = $event->getStart();

							//Update start datetime
							$start->setDateTime($session['start']->format(\DateTime::ISO8601));

							//Set end
							$end = $event->getEnd();

							//Update stop datetime
							$end->setDateTime($session['stop']->format(\DateTime::ISO8601));

							try {
								//Update the event
								$updatedEvent = $googleCalendar->events->update($token['calendar'], $event->getId(), $event);
							//Catch exception
							} catch(\Google\Service\Exception $e) {
								//Display exception
								//TODO: handle codes here https://developers.google.com/calendar/api/guides/errors
								echo 'Exception '.$e->getCode().':'.$e->getMessage().' in '.$e->getFile().' +'.$e->getLine()."\n";
								echo $e->getTraceAsString()."\n";

								//Return failure
								return self::FAILURE;
							}
						}

						//Drop from events array
						unset($events[$sessionId]);
					}
				}

				//Remaining events to drop
				foreach($events as $eventId => $event) {
					//Non canceled events
					if ($event->getStatus() == 'confirmed') {
						try {
							//Delete the event
							$googleCalendar->events->delete($token['calendar'], $event->getId());
						//Catch exception
						} catch(\Google\Service\Exception $e) {
							//Display exception
							//TODO: handle codes here https://developers.google.com/calendar/api/guides/errors
							echo 'Exception '.$e->getCode().':'.$e->getMessage().' in '.$e->getFile().' +'.$e->getLine()."\n";
							echo $e->getTraceAsString()."\n";

							//Return failure
							return self::FAILURE;
						}
					}
				}
			}
		}

		//Return success
		return self::SUCCESS;
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias(): string {
		return 'rapsys_air';
	}
}
