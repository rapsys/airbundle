<?php

namespace Rapsys\AirBundle\Controller;

use Google\Service\Calendar;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

#use Rapsys\AirBundle\Entity\Slot;
#use Rapsys\AirBundle\Entity\Session;
#use Rapsys\AirBundle\Entity\Location;
#use Rapsys\AirBundle\Entity\User;
#use Rapsys\AirBundle\Entity\Snippet;

class CalendarController extends DefaultController {
	/**
	 * Calendar authorization
	 *
	 * @desc Initiate calendar oauth process
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Prevent non-admin to access here
		$this->denyAccessUnlessGranted('ROLE_ADMIN', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));

		//Fetch doctrine
		#$doctrine = $this->getDoctrine();

		//Set section
		$section = $this->translator->trans('Calendar oauth form');

		//Set description
		$this->context['description'] = $this->translator->trans('Initiate calendar oauth process');

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\CalendarType', ['calendar' => $this->config['calendar']['calendar'], 'prefix' => $this->config['calendar']['prefix']], [
			'action' => $this->generateUrl('rapsys_air_calendar'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//When empty use config project
				$data['project'] = $data['project']?:$this->config['calendar']['project'];

				//When empty use config client
				$data['client'] = $data['client']?:$this->config['calendar']['client'];

				//When empty use config secret
				$data['secret'] = $data['secret']?:$this->config['calendar']['secret'];

				//Get google client
				$googleClient = new \Google\Client(
					[
						'application_name' => $data['project'],
						'client_id' => $data['client'],
						'client_secret' => $data['secret'],
						'redirect_uri' => $redirect = $this->generateUrl('rapsys_air_calendar_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
						'scopes' => [Calendar::CALENDAR, Calendar::CALENDAR_EVENTS],
						'access_type' => 'offline',
						'approval_prompt' => 'force'
					]
				);

#				//Set application name
#				$googleClient->setApplicationName($data['project']);
#
#				//Set client
#				$googleClient->setClientId($data['client']);
#
#				//Set client secret
#				$googleClient->setClientSecret($data['secret']);
#
#				//Add calendar scope
#				//XXX: required to create the airlibre calendar ?
#				$googleClient->addScope(Calendar::CALENDAR);
#				//Add calendar events scope
#				$googleClient->addScope(Calendar::CALENDAR_EVENTS);
#
#				//Set redirect uri
#				$googleClient->setRedirectUri($redirect = $this->generateUrl('rapsys_air_calendar_callback', [], UrlGeneratorInterface::ABSOLUTE_URL));
#
#				//Set offline access
#				$googleClient->setAccessType('offline');
#
#				//Set included scopes
#				//TODO: remove that useless of check scopes in callback
#				$googleClient->setIncludeGrantedScopes(true);

				//Set login hint
				#$googleClient->setLoginHint('rapsys.eu@gmail.com');

				//Set prompt
				//TODO: force refresh token creation with approval prompt
				#$googleClient->setApprovalPrompt('consent');

				//Get auth url
				$authUrl = $googleClient->createAuthUrl();

				//Get session
				$session = $request->getSession();

				//Store calendar, prefix, project, client and secret in session
				$session->set('calendar.calendar', $data['calendar']);
				$session->set('calendar.prefix', $data['prefix']);
				$session->set('calendar.project', $data['project']);
				$session->set('calendar.client', $data['client']);
				$session->set('calendar.secret', $data['secret']);
				$session->set('calendar.redirect', $redirect);

				//Redirect externally
				return $this->redirect($authUrl);
			}
		}

		//Render template
		return $this->render('@RapsysAir/calendar/index.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
	}

	/**
	 * List all sessions for the organizer
	 *
	 * @desc Display all sessions for the user with an application or login form
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function callback(Request $request) {
		//Set section
		$section = $this->translator->trans('Calendar callback');

		//Set description
		$this->context['description'] = $this->translator->trans('Finish calendar oauth process');

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//With code
		if (!empty($code = $request->get('code'))) {
			//Get session
			$session = $request->getSession();

			//Retrieve calendar
			$calendar = $session->get('calendar.calendar');

			//Retrieve prefix
			$prefix = $session->get('calendar.prefix');

			//Retrieve project
			$project = $session->get('calendar.project');

			//Retrieve client id
			$client = $session->get('calendar.client');

			//Retrieve secret
			$secret = $session->get('calendar.secret');

			//Retrieve redirect
			$redirect = $session->get('calendar.redirect');

			//Get google client
			#$googleClient = new \Google\Client(['application_name' => $project, 'client_id' => $client, 'client_secret' => $secret, 'redirect_uri' => $redirect]);
			$googleClient = new \Google\Client(
				[
					'application_name' => $project,
					'client_id' => $client,
					'client_secret' => $secret,
					'redirect_uri' => $redirect,
					'scopes' => [Calendar::CALENDAR, Calendar::CALENDAR_EVENTS],
					'access_type' => 'offline',
					'approval_prompt' => 'force'
				]
			);

			//Authenticate with code
			if (!empty($token = $googleClient->authenticate($code))) {
				//With error
				if (!empty($token['error'])) {
					$this->context['error'] = $this->translator->trans(ucfirst(str_replace('_', ' ', $token['error'])));
				//Without refresh token
				} elseif (empty($token['refresh_token'])) {
					$this->context['error'] = $this->translator->trans('Missing refresh token');
				//With valid token
				} else {
					//Retrieve cache object
					$cache = new FilesystemAdapter($this->config['cache']['namespace'], $this->config['cache']['lifetime'], $this->config['path']['cache']);

					//Retrieve calendars
					$cacheCalendars = $cache->getItem('calendars');

					//Init calendar
					$calendars = [];

					//With calendars
					if ($cacheCalendars->isHit()) {
						//Retrieve calendar
						$calendars = $cacheCalendars->get();
					}

					//With empty client
					if (empty($calendars[$client])) {
						//Store client
						$calendars[$client] = [
							'project' => $project,
							'secret' => $secret,
							'redirect' => $redirect,
							'tokens' => []
						];
					}

					//Add token
					$calendars[$client]['tokens'][$token['access_token']] = [
						'calendar' => $calendar,
						'prefix' => $prefix,
						'refresh' => $token['refresh_token'],
						'expire' => $token['expires_in'],
						'scope' => $token['scope'],
						'type' => $token['token_type'],
						'created' => $token['created']
					];

					//Store calendar
					$cacheCalendars->set($calendars);

					//Save calendar
					$cache->save($cacheCalendars);

					//Set message
					$this->context['message'] = $this->translator->trans('Token stored for project '.$project);
				}
			//With failed authenticate
			} else {
				$this->context['error'] = $this->translator->trans('Client authenticate failed');
			}
		//With error
		} elseif (!empty($error = $request->get('error'))) {
			$this->context['error'] = $this->translator->trans(ucfirst(str_replace('_', ' ', $error)));
		}

		//Render template
		return $this->render('@RapsysAir/calendar/callback.html.twig', ['title' => $title, 'section' => $section]+$this->context);
	}
}
