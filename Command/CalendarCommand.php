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
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventExtendedProperties;
use Google\Service\Calendar\EventSource;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
 * Synchronize sessions in users' google calendar
 */
class CalendarCommand extends Command {
	/**
	 * Set description
	 *
	 * Shown with bin/console list
	 */
	protected string $description = 'Synchronize sessions in users\' calendar';

	/**
	 * Set help
	 *
	 * Shown with bin/console --help rapsysair:calendar
	 */
	protected string $help = 'This command synchronize sessions in users\' google calendar';

	/**
	 * Set domain
	 */
	protected string $domain;

	/**
	 * Set item
	 *
	 * Cache item instance
	 */
	protected ItemInterface $item;

	/**
	 * Set prefix
	 */
	protected string $prefix;

	/**
	 * Set service
	 *
	 * Google calendar instance
	 */
	protected Calendar $service;

	/**
	 * {@inheritdoc}
	 *
	 * @param CacheInterface $cache The cache instance
	 * @param Client $google The google client instance
	 * @param DefaultMarkdown $markdown The markdown instance
	 */
	public function __construct(protected ManagerRegistry $doctrine, protected string $locale, protected RouterInterface $router, protected SluggerUtil $slugger, protected TranslatorInterface $translator, protected CacheInterface $cache, protected Client $google, protected DefaultMarkdown $markdown) {
		//Call parent constructor
		parent::__construct($this->doctrine, $this->locale, $this->router, $this->slugger, $this->translator);

		//Replace google client redirect uri
		$this->google->setRedirectUri($this->router->generate($this->google->getRedirectUri(), [], UrlGeneratorInterface::ABSOLUTE_URL));
	}

	/**
	 * Process the attribution
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		//Get domain
		$this->domain = $this->router->getContext()->getHost();

		//Get manager
		$manager = $this->doctrine->getManager();

		//Convert from any to latin, then to ascii and lowercase
		$trans = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');

		//Replace every non alphanumeric character by dash then trim dash
		$this->prefix = preg_replace(['/(\.[a-z0-9]+)+$/', '/[^a-v0-9]+/'], '', $trans->transliterate($this->domain));

		//With too short prefix
		if ($this->prefix === null || strlen($this->prefix) < 4) {
			//Throw domain exception
			throw new \DomainException('Prefix too short: '.$this->prefix);
		}

		//Iterate on google tokens
		foreach($tokens = $this->doctrine->getRepository(GoogleToken::class)->findAllIndexed() as $tid => $token) {
			//Clear client cache before changing access token
			//TODO: set a per token cache ?
			$this->google->getCache()->clear();

			//Set access token
			$this->google->setAccessToken(
				[
					'access_token' => $token['access'],
					'refresh_token' => $token['refresh'],
					'created' => $token['created']->getTimestamp(),
					'expires_in' => $token['expired']->getTimestamp() - (new \DateTime('now'))->getTimestamp()
				]
			);

			//With expired token
			if ($this->google->isAccessTokenExpired()) {
				//Refresh token
				if (($gRefresh = $this->google->getRefreshToken()) && ($gToken = $this->google->fetchAccessTokenWithRefreshToken($gRefresh)) && empty($gToken['error'])) {
					//Get google token
					$googleToken = $this->doctrine->getRepository(GoogleToken::class)->findOneById($token['id']);

					//Set access token
					$googleToken->setAccess($gToken['access_token']);

					//Set expires
					$googleToken->setExpired(new \DateTime('+'.$gToken['expires_in'].' second'));

					//Set refresh
					$googleToken->setRefresh($gToken['refresh_token']);

					//Queue google token save
					$manager->persist($googleToken);

					//Flush to get the ids
					$manager->flush();
				//Refresh failed
				} else {
					//Show error
					fprintf(STDERR, 'Unable to refresh token %d: %s', $token['id'], $gToken['error']?:'');

					//TODO: warn user by mail ?

					//Skip to next token
					continue;
				}
			}

			//Get google calendar service
			$this->service = new Calendar($this->google);

			//Iterate on google calendars
			foreach($calendars = $token['calendars'] as $cid => $calendar) {
				//Set start
				$synchronized = null;

				//Set cache key
				$cacheKey = 'command.calendar.'.$this->slugger->short($calendar['mail']);

				//XXX: TODO: remove DEBUG
				#$this->cache->delete($cacheKey);

				//Retrieve calendar events
				try {
					//Get events
					$events = $this->cache->get(
						//Cache key
						//XXX: set to command.calendar.$mail
						$cacheKey,
						//Fetch mail calendar event list
						function (ItemInterface $item) use ($calendar, &$synchronized): array {
							//Expire after 1h
							$item->expiresAfter(3600);

							//Set synchronized
							$synchronized = new \DateTime('now');

							//Init events
							$events = [];

							//Set filters
							//TODO: add a filter to only retrieve
							$filters = [
								//XXX: every event even deleted one to be able to update them
								'showDeleted' => true,
								//XXX: every instances
								'singleEvents' => false,
								//XXX: select only domain events
								'privateExtendedProperty' => 'domain='.$this->domain
								#TODO: restrict events even more by time or updated datetime ? (-1 week to +2 week)
								//TODO: fetch events one day before and one day after to avoid triggering double insert duplicate key 409 errors :=) on google
								#'timeMin' => $period->getStartDate()->format(\DateTime::ISO8601),
								#'timeMax' => $period->getEndDate()->format(\DateTime::ISO8601)
								/*, 'iCalUID' => 'airlibre/?????'*//*'orderBy' => 'startTime', */
								//updatedMin => new \DateTime('-1 week') ?
							];

							//Set page token
							$pageToken = null;

							//Iterate until next page token is null
							do {
								//Get calendar events list
								//XXX: see vendor/google/apiclient-services/src/Calendar/Resource/Events.php +289
								$eventList = $this->service->events->listEvents($calendar['mail'], ['pageToken' => $pageToken]+$filters);

								//Iterate on items
								foreach($eventList->getItems() as $event) {
									//With extended properties
									if (($properties = $event->getExtendedProperties()) && ($private = $properties->getPrivate()) && isset($private['id']) && ($id = $private['id']) && isset($private['domain']) && $private['domain'] == $this->domain) {
										//Add event
										$events[$id] = $event;
									//XXX: 3rd party events without matching prefix and id are skipped
									#} else {
									#	#echo 'Skipping '.$event->getId().':'.$event->getSummary()."\n";
									#	echo 'Skipping '.$id.':'.$event->getSummary()."\n";
									}
								}
							} while ($pageToken = $eventList->getNextPageToken());

							//Return events
							return $events;
						}
					);
				//Catch exception
				} catch(\Google\Service\Exception $e) {
					//With 401 or code
					//XXX: see https://cloud.google.com/apis/design/errors
					if ($e->getCode() == 401 || $e->getCode() == 403) {
						//Show error
						fprintf(STDERR, 'Unable to list calendar %d events: %s', $calendar['id'], $e->getMessage()?:'');

						//TODO: warn user by mail ?

						//Skip to next token
						continue;
					}

					//Throw error
					throw new \LogicException('Calendar event list failed', 0, $e);
				}

				//Store cache item
				$this->item = $this->cache->getItem($cacheKey);

				//Iterate on sessions to update
				foreach($this->doctrine->getRepository(Session::class)->findAllByUserIdSynchronized($token['uid'], $calendar['synchronized']) as $session) {
					//Start exception catching
					try {
						//Without event
						if (!isset($events[$session['id']])) {
							//Insert event
							$this->insert($calendar['mail'], $session);
						//With locked session
						} elseif (($event = $events[$session['id']]) && !empty($session['locked'])) {
							//Delete event
							$sid = $this->delete($calendar['mail'], $event);

							//Drop from events array
							unset($events[$sid]);
						//With event to update
						} elseif ($session['modified'] > (new \DateTime($event->getUpdated()))) {
							//Update event
							$sid = $this->update($calendar['mail'], $event, $session);

							//Drop from events array
							unset($events[$sid]);
						}
					//Catch exception
					} catch(\Google\Service\Exception $e) {
						//Identifier already exists
						if ($e->getCode() == 409) {
							//Get calendar event
							//XXX: see vendor/google/apiclient-services/src/Calendar/Resource/Events.php +81
							$event = $this->service->events->get($calendar['mail'], $this->prefix.$session['id']);

							//Update required
							if ($session['modified'] > (new \DateTime($event->getUpdated()))) {
								//Update event
								$sid = $this->update($calendar['mail'], $event, $session);

								//Drop from events array
								unset($events[$sid]);
							}
						//TODO: handle other codes gracefully ? (503 & co)
						//Other errors
						} else {
							//Throw error
							throw new \LogicException(sprintf('Calendar %s event %s operation failed', $calendar, $this->prefix.$session['id']), 0, $e);
						}
					}
				}

				//Get all sessions
				$sessions = $this->doctrine->getRepository(Session::class)->findAllByUserIdSynchronized($token['uid']);

				//Remaining events to drop
				foreach($events as $eid => $event) {
					//With events updated since last synchronized
					if ($event->getStatus() == 'confirmed' && (new \DateTime($event->getUpdated())) > $calendar['synchronized']) {
						//TODO: Add a try/catch here to handle error codes gracefully (503 & co) ?
						//With event to update
						if (isset($sessions[$eid]) && ($session = $sessions[$eid]) && empty($session['locked'])) {
							//Update event
							$sid = $this->update($calendar['mail'], $event, $session);

							//Drop from events array
							unset($events[$sid]);
						//With locked or unknown session
						} else {
							//Delete event
							$sid = $this->delete($calendar['mail'], $event);

							//Drop from events array
							unset($events[$sid]);
						}
					}
				}

				//Persist cache item
				$this->cache->commit();

				//With synchronized
				//XXX: only store synchronized on run without caching
				if ($synchronized) {
					//Get google calendar
					$googleCalendar = $this->doctrine->getRepository(GoogleCalendar::class)->findOneById($calendar['id']);

					//Set synchronized
					$googleCalendar->setSynchronized($synchronized);

					//Queue google calendar save
					$manager->persist($googleCalendar);

					//Flush to get the ids
					$manager->flush();
				}
			}
		}

		//Return success
		return self::SUCCESS;
	}

	/**
	 * Delete event
	 *
	 * @param string $calendar The calendar mail
	 * @param Event $event The google event instance
	 * @return void
	 */
	function delete(string $calendar, Event $event): int {
		//Get cache events
		$cacheEvents = $this->item->get();

		//Get event id
		$eid = $event->getId();

		//Delete the event
		$this->service->events->delete($calendar, $eid);

		//Set sid
		$sid = intval(substr($event->getId(), strlen($this->prefix)));

		//Remove from events and cache events
		unset($cacheEvents[$sid]);

		//Set cache events
		$this->item->set($cacheEvents);

		//Save cache item
		$this->cache->saveDeferred($this->item);

		//Return session id
		return $sid;
	}

	/**
	 * Fill event
	 *
	 * TODO: add domain based/calendar mail specific templates ?
	 *
	 * @param array $session The session instance
	 * @param ?Event $event The event instance
	 * @return Event The filled event
	 */
	function fill(array $session, ?Event $event = null): Event {
		//Init private properties
		$private = [
			'id' => $session['id'],
			'domain' => $this->domain,
			'updated' => $session['modified']->format(\DateTime::ISO8601)
		];

		//Init shared properties
		//TODO: validate for constraints here ??? https://developers.google.com/calendar/api/guides/extended-properties
		//TODO: drop shared as unused ???
		$shared = [
			'gps' => $session['l_latitude'].','.$session['l_longitude']
		];

		//Init source
		$source = new EventSource(
			[
				'title' => $this->translator->trans('%dance% %id% by %pseudonym%', ['%id%' => $session['id'], '%dance%' => $this->translator->trans($session['ad_name'].' '.lcfirst($session['ad_type'])), '%pseudonym%' => $session['au_pseudonym']]).' '.$this->translator->trans('at '.$session['l_title']),
				'url' => $this->router->generate('rapsysair_session_view', ['id' => $session['id'], 'location' => $this->slugger->slug($this->translator->trans($session['l_title'])), 'dance' => $this->slugger->slug($this->translator->trans($session['ad_name'].' '.lcfirst($session['ad_type']))), 'user' => $this->slugger->slug($session['au_pseudonym'])], UrlGeneratorInterface::ABSOLUTE_URL)
			]
		);

		//Init location
		$description = 'Emplacement :'."\n".$this->translator->trans($session['l_description']);
		$shared['location'] = strip_tags($this->translator->trans($session['l_description']));

		//Add description when available
		if(!empty($session['p_description'])) {
			$description .= "\n\n".'Description :'."\n".strip_tags(preg_replace('!<a href="([^"]+)"(?: title="[^"]+")?'.'>([^<]+)</a>!', '\1', $this->markdown->convert(strip_tags($session['p_description']))));
			$shared['description'] = $this->markdown->convert(strip_tags($session['p_description']));
		}

		//Add class when available
		if (!empty($session['p_class'])) {
			$description .= "\n\n".'Classe :'."\n".$session['p_class'];
			$shared['class'] = $session['p_class'];
		}

		//Add contact when available
		if (!empty($session['p_contact'])) {
			$description .= "\n\n".'Contact :'."\n".$session['p_contact'];
			$shared['contact'] = $session['p_contact'];
		}

		//Add donate when available
		if (!empty($session['p_donate'])) {
			$description .= "\n\n".'Contribuer :'."\n".$session['p_donate'];
			$shared['donate'] = $session['p_donate'];
		}

		//Add link when available
		if (!empty($session['p_link'])) {
			$description .= "\n\n".'Site :'."\n".$session['p_link'];
			$shared['link'] = $session['p_link'];
		}

		//Add profile when available
		if (!empty($session['p_profile'])) {
			$description .= "\n\n".'Réseau social :'."\n".$session['p_profile'];
			$shared['profile'] = $session['p_profile'];
		}

		//Set properties
		$properties = new EventExtendedProperties(
			[
				//Set private property
				'private' => $private,
				//Set shared property
				'shared' => $shared
			]
		);

		//Without event
		if ($event === null) {
			//Init event
			$event = new Event(
				[
					//Id must match /^[a-v0-9]{5,}$/
					//XXX: see https://developers.google.com/calendar/api/v3/reference/events/insert#id
					'id' => $this->prefix.$session['id'],
					'summary' => $source->getTitle(),
					'description' => $description,
					'status' => empty($session['a_canceled'])?'confirmed':'cancelled',
					'location' => implode(' ', [$session['l_address'], $session['l_zipcode'], $session['l_city']]),
					'source' => $source,
					'extendedProperties' => $properties,
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
		//With event
		} else {
			//Set summary
			$event->setSummary($source->getTitle());

			//Set description
			$event->setDescription($description);

			//Set status
			$event->setStatus(empty($session['a_canceled'])?'confirmed':'cancelled');

			//Set location
			$event->setLocation(implode(' ', [$session['l_address'], $session['l_zipcode'], $session['l_city']]));

			//Get source
			#$eventSource = $event->getSource();

			//Update source title
			#$eventSource->setTitle($source->getTitle());

			//Update source url
			#$eventSource->setUrl($source->getUrl());

			//Set source
			$event->setSource($source);

			//Get extended properties
			#$extendedProperties = $event->getExtendedProperties();

			//Update private
			#$extendedProperties->setPrivate($properties->getPrivate());

			//Update shared
			#$extendedProperties->setShared($properties->getShared());

			//Set properties
			$event->setExtendedProperties($properties);

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
		}

		//Return event
		return $event;
	}

	/**
	 * Insert event
	 *
	 * @param string $calendar The calendar mail
	 * @param array $session The session instance
	 * @return void
	 */
	function insert(string $calendar, array $session): void {
		//Get event
		$event = $this->fill($session);

		//Get cache events
		$cacheEvents = $this->item->get();

		//Insert in cache event
		$cacheEvents[$session['id']] = $this->service->events->insert($calendar, $event);

		//Set cache events
		$this->item->set($cacheEvents);

		//Save cache item
		$this->cache->saveDeferred($this->item);
	}

	/**
	 * Update event
	 *
	 * @param string $calendar The calendar mail
	 * @param Event $event The google event instance
	 * @param array $session The session instance
	 * @return int The session id
	 */
	function update(string $calendar, Event $event, array $session): int {
		//Get event
		$event = $this->fill($session, $event);

		//Get cache events
		$cacheEvents = $this->item->get();

		//Update in cache events
		$cacheEvents[$session['id']] = $this->service->events->update($calendar, $event->getId(), $event);

		//Set cache events
		$this->item->set($cacheEvents);

		//Save cache item
		$this->cache->saveDeferred($this->item);

		//Return session id
		return $session['id'];
	}
}
