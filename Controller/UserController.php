<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Controller;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Rapsys\UserBundle\Controller\UserController as BaseUserController;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\GoogleCalendar;
use Rapsys\AirBundle\Entity\GoogleToken;
use Rapsys\AirBundle\Entity\User;

/**
 * {@inheritdoc}
 */
class UserController extends BaseUserController {
	/**
	 * Set google client scopes
	 */
	const googleScopes = [\Google\Service\Calendar::CALENDAR_EVENTS, \Google\Service\Calendar::CALENDAR, \Google\Service\Oauth2::USERINFO_EMAIL];

	/**
	 * {@inheritdoc}
	 */
	public function edit(Request $request, string $hash, string $mail): Response {
		//With invalid hash
		if ($hash != $this->slugger->hash($mail)) {
			//Throw bad request
			throw new BadRequestHttpException($this->translator->trans('Invalid %field% field: %value%', ['%field%' => 'hash', '%value%' => $hash]));
		}

		//Get mail
		$mail = $this->slugger->unshort($smail = $mail);

		//With existing subscriber
		if (empty($user = $this->doctrine->getRepository($this->config['class']['user'])->findOneByMail($mail))) {
			//Throw not found
			//XXX: prevent slugger reverse engineering by not displaying decoded mail
			throw $this->createNotFoundException($this->translator->trans('Unable to find account %mail%', ['%mail%' => $smail]));
		}

		//Prevent access when not admin, user is not guest and not currently logged user
		if (!$this->checker->isGranted('ROLE_ADMIN') && $user != $this->security->getUser() || !$this->checker->isGranted('IS_AUTHENTICATED_FULLY')) {
			//Throw access denied
			//XXX: prevent slugger reverse engineering by not displaying decoded mail
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access user: %mail%', ['%mail%' => $smail]));
		}

		//Create the RegisterType form and give the proper parameters
		$edit = $this->factory->create($this->config['edit']['view']['edit'], $user, [
			//Set action to register route name and context
			'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $hash]+$this->config['route']['edit']['context']),
			//Set civility class
			'civility_class' => $this->config['class']['civility'],
			//Set civility default
			'civility_default' => $this->doctrine->getRepository($this->config['class']['civility'])->findOneByTitle($this->config['default']['civility']),
			//Set country class
			'country_class' => $this->config['class']['country'],
			//Set country default
			'country_default' => $this->doctrine->getRepository($this->config['class']['country'])->findOneByTitle($this->config['default']['country']),
			//Set country favorites
			'country_favorites' => $this->doctrine->getRepository($this->config['class']['country'])->findByTitle($this->config['default']['country_favorites']),
			//Set dance
			'dance' => $this->checker->isGranted('ROLE_ADMIN'),
			//Set dance choices
			'dance_choices' => $danceChoices = $this->doctrine->getRepository($this->config['class']['dance'])->findChoicesAsArray(),
			//Set dance default
			#'dance_default' => /*$this->doctrine->getRepository($this->config['class']['dance'])->findOneByNameType($this->config['default']['dance'])*/null,
			//Set dance favorites
			'dance_favorites' => $this->doctrine->getRepository($this->config['class']['dance'])->findIdByNameTypeAsArray($this->config['default']['dance_favorites']),
			//Set subscription
			'subscription' => $this->checker->isGranted('ROLE_ADMIN'),
			//Set subscription choices
			'subscription_choices' => $subscriptionChoices = $this->doctrine->getRepository($this->config['class']['user'])->findChoicesAsArray(),
			//Set subscription default
			#'subscription_default' => /*$this->doctrine->getRepository($this->config['class']['user'])->findOneByPseudonym($this->config['default']['subscription'])*/null,
			//Set subscription favorites
			'subscription_favorites' => $this->doctrine->getRepository($this->config['class']['user'])->findIdByPseudonymAsArray($this->config['default']['subscription_favorites']),
			//Disable mail
			'mail' => $this->checker->isGranted('ROLE_ADMIN'),
			//Disable pseudonym
			'pseudonym' => $this->checker->isGranted('ROLE_GUEST'),
			//Disable password
			'password' => false,
			//Set method
			'method' => 'POST'
		]+$this->config['edit']['field']);

		//With admin role
		if ($this->checker->isGranted('ROLE_ADMIN')) {
			//Create the ResetType form and give the proper parameters
			$reset = $this->factory->create($this->config['edit']['view']['reset'], $user, [
				//Set action to register route name and context
				'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $hash]+$this->config['route']['edit']['context']),
				//Disable mail
				'mail' => false,
				//Set method
				'method' => 'POST'
			]);

			//With post method
			if ($request->isMethod('POST')) {
				//Refill the fields in case the form is not valid.
				$reset->handleRequest($request);

				//With reset submitted and valid
				if ($reset->isSubmitted() && $reset->isValid()) {
					//Set data
					$data = $reset->getData();

					//Set password
					$data->setPassword($this->hasher->hashPassword($data, $data->getPassword()));

					//Queue user password save
					$this->manager->persist($data);

					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Account %mail% password updated', ['%mail%' => $mail]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $hash]+$this->config['route']['edit']['context']);
				}
			}

			//Add reset view
			$this->config['edit']['view']['context']['reset'] = $reset->createView();

			//Add google calendar array
			$this->config['edit']['view']['context']['calendar'] = [
				//Form by mail
				'form' => [],
				//Uri to link account
				'link' => null,
				//Logo
				'logo' => [
					'png' => '@RapsysAir/png/calendar.png',
					'svg' => '@RapsysAir/svg/calendar.svg'
				]
			];

			//Get google client
			$googleClient = new \Google\Client(
				[
					'application_name' => $request->server->get('GOOGLE_PROJECT'),
					'client_id' => $request->server->get('GOOGLE_CLIENT'),
					'client_secret' => $request->server->get('GOOGLE_SECRET'),
					'redirect_uri' => $this->generateUrl('rapsys_air_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
					'scopes' => self::googleScopes,
					'access_type' => 'offline',
					'login_hint' => $user->getMail(),
					//XXX: see https://stackoverflow.com/questions/10827920/not-receiving-google-oauth-refresh-token
					#'approval_prompt' => 'force'
					'prompt' => 'consent'
				]
			);

			//With user tokens
			if (!($googleTokens = $user->getGoogleTokens())->isEmpty()) {
				//Iterate on each google token
				//XXX: either we finish with a valid token set or a logic exception after token removal
				foreach($googleTokens as $googleToken) {
					//Clear client cache before changing access token
					//TODO: set a per token cache ?
					$googleClient->getCache()->clear();

					//Set access token
					$googleClient->setAccessToken(
						[
							'access_token' => $googleToken->getAccess(),
							'refresh_token' => $googleToken->getRefresh(),
							'created' => $googleToken->getCreated()->getTimestamp(),
							'expires_in' => $googleToken->getExpired()->getTimestamp() - (new \DateTime('now'))->getTimestamp(),
						]
					);

					//With expired token
					if ($googleClient->isAccessTokenExpired()) {
						//Refresh token
						if (($refresh = $googleClient->getRefreshToken()) && ($token = $googleClient->fetchAccessTokenWithRefreshToken($refresh)) && empty($token['error'])) {
							//Set access token
							$googleToken->setAccess($token['access_token']);

							//Set expires
							$googleToken->setExpired(new \DateTime('+'.$token['expires_in'].' second'));

							//Set refresh
							$googleToken->setRefresh($token['refresh_token']);

							//Queue google token save
							$this->manager->persist($googleToken);

							//Flush to get the ids
							$this->manager->flush();
						//Refresh failed
						} else {
							//Add error in flash message
							$this->addFlash(
								'error',
								$this->translator->trans(
									empty($token['error'])?'Unable to refresh token':'Unable to refresh token: %error%',
									empty($token['error'])?[]:['%error%' => str_replace('_', ' ', $token['error'])]
								)
							);

							//Remove user token
							$this->manager->remove($googleToken);

							//Flush to delete it
							$this->manager->flush();

							//Skip to next token
							continue;
						}
					}

					//XXX: TODO: remove DEBUG
					#$this->cache->delete('user.edit.calendar.'.$this->slugger->short($googleToken->getMail()));

					//Get calendars
					$calendars = $this->cache->get(
						//Set key to user.edit.$mail
						($calendarKey = 'user.edit.calendar.'.($googleShortMail = $this->slugger->short($googleMail = $googleToken->getMail()))),
						//Fetch mail calendar list
						function (ItemInterface $item) use ($googleClient): array {
							//Expire after 1h
							$item->expiresAfter(3600);

							//Get google calendar service
							$service = new \Google\Service\Calendar($googleClient);

							//Init calendars
							$calendars = [];

							//Init counter
							$count = 0;

							//Retrieve calendar
							try {
								//Set page token
								$pageToken = null;

								//Iterate until next page token is null
								do {
									//Get token calendar list
									//XXX: require permission to read and write events
									$calendarList = $service->calendarList->listCalendarList(['pageToken' => $pageToken, 'minAccessRole' => 'writer', 'showHidden' => true]);

									//Iterate on items
									foreach($calendarList->getItems() as $calendarItem) {
										//With primary calendar
										if ($calendarItem->getPrimary()) {
											//Add primary calendar
											//XXX: use primary as key as described in google api documentation
											$calendars = ['primary' => $this->translator->trans('Primary') /*$calendarItem->getSummary()*/] + $calendars;
										//With secondary calendar
										} else {
											//Add secondary calendar
											//XXX: Append counter to make sure summary is unique for later array_flip call
											$calendars += [$calendarItem->getId() => $calendarItem->getSummary().' ('.++$count.')'];
										}
									}
								} while ($pageToken = $calendarList->getNextPageToken());
							//Catch exception
							} catch(\Google\Service\Exception $e) {
								//Throw error
								throw new \LogicException('Calendar list failed', 0, $e);
							}

							//Cache calendars
							return $calendars;
						}
					);

					//Set formData array
					$formData = ['calendar' => []];

					//With google calendars
					if (!($googleCalendars = $googleToken->getGoogleCalendars())->isEmpty()) {
						//Iterate on each google calendars
						foreach($googleCalendars as $googleCalendar) {
							//With existing google calendar
							if (isset($calendars[$googleCalendar->getMail()])) {
								//Add google calendar to form data
								$formData['calendar'][] = $googleCalendar->getMail();
							} else {
								//Remove google calendar from database
								$this->manager->remove($googleCalendar);

								//Flush to persist ids
								$this->manager->flush();
							}
						}
					}

					//XXX: TODO: remove DEBUG
					#header('Content-Type: text/plain');

					//TODO: add feature to filter synchronized data (OrganizerId/DanceId)
					//TODO: add feature for alerts (-30min/-1h) ?
					//[Direct link to calendar ?][Direct link to calendar settings ?][Alerts][Remove]

					//Create the CalendarType form and give the proper parameters
					$form = $this->factory->createNamed('calendar_'.$googleShortMail, 'Rapsys\AirBundle\Form\CalendarType', $formData, [
						//Set action to register route name and context
						'action' => $this->generateUrl($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $hash]+$this->config['route']['edit']['context']),
						//Set calendar choices
						//XXX: unique calendar summary required by choice widget is guaranteed by appending ' (x)' to secondary calendars earlier
						'calendar_choices' => array_flip($calendars),
						//Set method
						'method' => 'POST'
					]);

					//With post method
					if ($request->isMethod('POST')) {
						//Refill the fields in case the form is not valid.
						$form->handleRequest($request);

						//With reset submitted and valid
						if ($form->isSubmitted() && $form->isValid()) {
							//Set data
							$data = $form->getData();

							//Refresh button
							if (($clicked = $form->getClickedButton()->getName()) == 'refresh') {
								//Remove calendar key
								$this->cache->delete($calendarKey);

								//Add notice
								$this->addFlash('notice', $this->translator->trans('Account %mail% calendars updated', ['%mail%' => $googleMail]));
							//Add button
							} elseif ($clicked == 'add') {
								//Get google calendar service
								$service = new \Google\Service\Calendar($googleClient);

								//Add calendar
								try {
									//Instantiate calendar
									$calendar = new \Google\Service\Calendar\Calendar(
										[
											'summary' => $this->translator->trans($this->config['context']['site']['title']),
											'timeZone' => date_default_timezone_get()
										]
									);

									//Insert calendar
									$service->calendars->insert($calendar);
								//Catch exception
								} catch(\Google\Service\Exception $e) {
									//Throw error
									throw new \LogicException('Calendar insert failed', 0, $e);
								}

								//Remove calendar key
								$this->cache->delete($calendarKey);

								//Add notice
								$this->addFlash('notice', $this->translator->trans('Account %mail% calendar added', ['%mail%' => $googleMail]));
							//Delete button
							} elseif ($clicked == 'delete') {
								//Get google calendar service
								$service = new \Google\Service\Calendar($googleClient);

								//Remove calendar
								try {
									//Set site title
									$siteTitle = $this->translator->trans($this->config['context']['site']['title']);

									//Iterate on calendars
									foreach($calendars as $calendarId => $calendarSummary) {
										//With calendar matching site title
										if (substr($calendarSummary, 0, strlen($siteTitle)) == $siteTitle) {
											//Delete the calendar
											$service->calendars->delete($calendarId);
										}
									}
								//Catch exception
								} catch(\Google\Service\Exception $e) {
									//Throw error
									throw new \LogicException('Calendar delete failed', 0, $e);
								}

								//Remove calendar key
								$this->cache->delete($calendarKey);

								//Add notice
								$this->addFlash('notice', $this->translator->trans('Account %mail% calendars deleted', ['%mail%' => $googleMail]));
							//Unlink button
							} elseif ($clicked == 'unlink') {
								//Iterate on each google calendars
								foreach($googleCalendars as $googleCalendar) {
									//Remove google calendar from database
									$this->manager->remove($googleCalendar);
								}

								//Remove google token from database
								$this->manager->remove($googleToken);

								//Flush to persist
								$this->manager->flush();

								//Revoke access token
								$googleClient->revokeToken($googleToken->getAccess());

								//With refresh token
								if ($refresh = $googleToken->getRefresh()) {
									//Revoke refresh token
									$googleClient->revokeToken($googleToken->getRefresh());
								}

								//Remove calendar key
								$this->cache->delete($calendarKey);

								//Add notice
								$this->addFlash('notice', $this->translator->trans('Account %mail% calendars unlinked', ['%mail%' => $googleMail]));
							//Submit button
							} else {
								//Flipped calendar data
								$dataCalendarFlip = array_flip($data['calendar']);

								//Iterate on each google calendars
								foreach($googleCalendars as $googleCalendar) {
									//Without calendar in flipped data
									if (!isset($dataCalendarFlip[$googleCalendarMail = $googleCalendar->getMail()])) {
										//Remove google calendar from database
										$this->manager->remove($googleCalendar);
									//With calendar in flipped data
									} else {
										//Remove google calendar from calendar data
										unset($data['calendar'][$dataCalendarFlip[$googleCalendarMail]]);
									}
								}

								//Iterate on remaining calendar data
								foreach($data['calendar'] as $googleCalendarMail) {
									//Create new google calendar
									//XXX: remove trailing ' (x)' from summary
									$googleCalendar = new GoogleCalendar($googleToken, $googleCalendarMail, preg_replace('/ \([0-9]\)$/', '', $calendars[$googleCalendarMail]));

									//Queue google calendar save
									$this->manager->persist($googleCalendar);
								}

								//Flush to persist ids
								$this->manager->flush();

								//Add notice
								$this->addFlash('notice', $this->translator->trans('Account %mail% calendars updated', ['%mail%' => $googleMail]));
							}

							//Redirect to cleanup the form
							return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail, 'hash' => $hash]+$this->config['route']['edit']['context']);
						}
					}

					//Add form view
					$this->config['edit']['view']['context']['calendar']['form'][$googleToken->getMail()] = $form->createView();
				}
			}

			//Add google calendar auth url
			$this->config['edit']['view']['context']['calendar']['link'] = $googleClient->createAuthUrl();
		}

		//With post method
		if ($request->isMethod('POST')) {
			//Refill the fields in case the form is not valid.
			$edit->handleRequest($request);

			//With edit submitted and valid
			if ($edit->isSubmitted() && $edit->isValid()) {
				//Set data
				$data = $edit->getData();

				//Queue user save
				$this->manager->persist($data);

				//Try saving in database
				try {
					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					//XXX: get mail from data as it may change
					$this->addFlash('notice', $this->translator->trans('Account %mail% updated', ['%mail%' => $mail = $data->getMail()]));

					//Redirect to cleanup the form
					return $this->redirectToRoute($this->config['route']['edit']['name'], ['mail' => $smail = $this->slugger->short($mail), 'hash' => $this->slugger->hash($smail)]+$this->config['route']['edit']['context']);
				//Catch double slug or mail
				} catch (UniqueConstraintViolationException $e) {
					//Add error message mail already exists
					$this->addFlash('error', $this->translator->trans('Account %mail% already exists', ['%mail%' => $data->getMail()]));
				}
			}
		//Without admin role
		//XXX: prefer a reset on login to force user unspam action
		} elseif (!$this->checker->isGranted('ROLE_ADMIN')) {
			//Add notice
			$this->addFlash('notice', $this->translator->trans('To change your password login with your mail and any password then follow the procedure'));
		}

		//Render view
		return $this->render(
			//Template
			$this->config['edit']['view']['name'],
			//Context
			['edit' => $edit->createView(), 'sent' => $request->query->get('sent', 0)]+$this->config['edit']['view']['context']
		);
	}

	/**
	 * Handle google callback
	 *
	 * @param Request $request The request
	 * @return Response The response
	 */
	public function googleCallback(Request $request): Response {
		//Without code
		if (empty($code = $request->query->get('code', ''))) {
			throw new \InvalidArgumentException('Query parameter code is empty');
		}

		//Without user
		if (empty($user = $this->getUser())) {
			throw new \LogicException('User is empty');
		}

		//Get google client
		$googleClient = new \Google\Client(
			[
				'application_name' => $request->server->get('GOOGLE_PROJECT'),
				'client_id' => $request->server->get('GOOGLE_CLIENT'),
				'client_secret' => $request->server->get('GOOGLE_SECRET'),
				'redirect_uri' => $this->generateUrl('rapsys_air_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
				'scopes' => self::googleScopes,
				'access_type' => 'offline',
				'login_hint' => $user->getMail(),
				#'approval_prompt' => 'force'
				'prompt' => 'consent'
			]
		);

		//Protect to extract failure
		try {
			//Authenticate with code
			if (!empty($token = $googleClient->authenticate($code))) {
				//With error
				if (!empty($token['error'])) {
					throw new \LogicException('Client authenticate failed: '.str_replace('_', ' ', $token['error']));
				//Without refresh token
				} elseif (empty($token['refresh_token'])) {
					throw new \LogicException('Refresh token is empty');
				//Without expires in
				} elseif (empty($token['expires_in'])) {
					throw new \LogicException('Expires in is empty');
				//Without scope
				} elseif (empty($token['scope'])) {
					throw new \LogicException('Scope in is empty');
				//Without valid scope
				} elseif (array_intersect(self::googleScopes, explode(' ', $token['scope'])) != self::googleScopes) {
					throw new \LogicException('Scope in is not valid');
				}

				//Get Oauth2 object
				$oauth2 = new \Google\Service\Oauth2($googleClient);

				//Protect user info get call
				try {
					//Retrieve user info
					$userInfo = $oauth2->userinfo->get();
				//Catch exception
				} catch(\Google\Service\Exception $e) {
					//Throw error
					throw new \LogicException('Userinfo get failed', 0, $e);
				}

				//With existing token
				if (
					//If available retrieve google token with matching mail
					$googleToken = array_reduce(
						$user->getGoogleTokens()->getValues(),
						function ($c, $i) use ($userInfo) {
							if ($i->getMail() == $userInfo['email']) {
								return $i;
							}
						},
						(object)[]
					)
				) {
					//Set mail
					//XXX: TODO: should already be set and not change, remove ?
					//XXX: TODO: store picture as well ?
					$googleToken->setMail($userInfo['email']);

					//Set access token
					$googleToken->setAccess($token['access_token']);

					//Set expires
					$googleToken->setExpired(new \DateTime('+'.$token['expires_in'].' second'));

					//Set refresh
					$googleToken->setRefresh($token['refresh_token']);
				} else {
					//Create new token
					//XXX: TODO: store picture as well ?
					$googleToken = new GoogleToken($user, $userInfo['email'], $token['access_token'], new \DateTime('+'.$token['expires_in'].' second'), $token['refresh_token']);
				}

				//Queue google token save
				$this->manager->persist($googleToken);

				//Flush to get the ids
				$this->manager->flush();

				//Add notice
				$this->addFlash('notice', $this->translator->trans('Account %mail% google token updated', ['%mail%' => $user->getMail()]));
			//With failed authenticate
			} else {
				throw new \LogicException('Client authenticate failed');
			}
		//Catch exception
		} catch(\Exception $e) {
			//Add notice
			$this->addFlash('error', $this->translator->trans('Account %mail% google token rejected: %error%', ['%mail%' => $user->getMail(), '%error%' => $e->getMessage()]));
		}

		//Redirect to user
		return $this->redirectToRoute('rapsysuser_edit', ['mail' => $short = $this->slugger->short($user->getMail()), 'hash' => $this->slugger->hash($short)]);
	}
}
