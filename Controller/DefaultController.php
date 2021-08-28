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

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Pdf\DisputePdf;

/**
 * {@inheritdoc}
 */
class DefaultController extends AbstractController {
	/**
	 * The about page
	 *
	 * @desc Display the about informations
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function about(Request $request): Response {
		//Set page
		$this->context['title'] = $this->translator->trans('About');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air about');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('about'),
			$this->translator->trans('Libre Air')
		];

		//Render template
		$response = $this->render('@RapsysAir/default/about.html.twig', $this->context);
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($request);

		//Return response
		return $response;
	}

	/**
	 * The contact page
	 *
	 * @desc Send a contact mail to configured contact
	 *
	 * @param Request $request The request instance
	 * @param MailerInterface $mailer The mailer instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function contact(Request $request, MailerInterface $mailer): Response {
		//Set page
		$this->context['title'] = $this->translator->trans('Contact');

		//Set description
		$this->context['description'] = $this->translator->trans('Contact Libre Air');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('contact'),
			$this->translator->trans('Libre Air'),
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('calendar')
		];

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', null, [
			'action' => $this->generateUrl('rapsys_air_contact'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Create message
				$message = (new TemplatedEmail())
					//Set sender
					->from(new Address($data['mail'], $data['name']))
					//Set recipient
					->to(new Address($this->context['contact']['mail'], $this->context['contact']['title']))
					//Set subject
					->subject($data['subject'])

					//Set path to twig templates
					->htmlTemplate('@RapsysAir/mail/contact.html.twig')
					->textTemplate('@RapsysAir/mail/contact.text.twig')

					//Set context
					->context(
						[
							'subject' => $data['subject'],
							'message' => strip_tags($data['message']),
						]+$this->context
					);

				//Try sending message
				//XXX: mail delivery may silently fail
				try {
					//Send message
					$mailer->send($message);

					//Redirect on the same route with sent=1 to cleanup form
					return $this->redirectToRoute($request->get('_route'), ['sent' => 1]+$request->get('_route_params'));
				//Catch obvious transport exception
				} catch(TransportExceptionInterface $e) {
					if ($message = $e->getMessage()) {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%: %message%', ['%mail%' => $this->context['contact']['mail'], '%message%' => $this->translator->trans($message)])));
					} else {
						//Add error message mail unreachable
						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%', ['%mail%' => $this->context['contact']['mail']])));
					}
				}
			}
		}

		//Render template
		return $this->render('@RapsysAir/form/contact.html.twig', ['form' => $form->createView(), 'sent' => $request->query->get('sent', 0)]+$this->context);
	}

	/**
	 * The dispute page
	 *
	 * @desc Generate a dispute document
	 *
	 * @param Request $request The request instance
	 * @param MailerInterface $mailer The mailer instance
	 *
	 * @return Response The rendered view or redirection
	 */
	public function dispute(Request $request, MailerInterface $mailer): Response {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_USER', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('User')]));

		//Set page
		$this->context['title'] = $this->translator->trans('Dispute');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air dispute');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('dispute'),
			$this->translator->trans('Libre Air'),
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('calendar')
		];

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\DisputeType', ['court' => 'Paris', 'abstract' => 'Pour constater cette prétendue infraction, les agents verbalisateurs ont pénétré dans un jardin privatif, sans visibilité depuis la voie publique, situé derrière un batiment privé, pour ce faire ils ont franchi au moins un grillage de chantier ou des potteaux métalliques séparant le terrain privé de la voie publique de l\'autre côté du batiment.'], [
			'action' => $this->generateUrl('rapsys_air_dispute'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isValid()) {
				//Get data
				$data = $form->getData();

				//Gathering offense
				if (!empty($data['offense']) && $data['offense'] == 'gathering') {
					//Add gathering
					$output = DisputePdf::genGathering($data['court'], $data['notice'], $data['agent'], $data['service'], $data['abstract'], $this->translator->trans($this->getUser()->getCivility()->getTitle()), $this->getUser()->getForename(), $this->getUser()->getSurname());
				//Traffic offense
				} elseif (!empty($data['offense'] && $data['offense'] == 'traffic')) {
					//Add traffic
					$output = DisputePdf::genTraffic($data['court'], $data['notice'], $data['agent'], $data['service'], $data['abstract'], $this->translator->trans($this->getUser()->getCivility()->getTitle()), $this->getUser()->getForename(), $this->getUser()->getSurname());
				//Unsupported offense
				} else {
					header('Content-Type: text/plain');
					die('TODO');
					exit;
				}

				//Send common headers
				header('Content-Type: application/pdf');

				//Send remaining headers
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');

				//Send content-length
				header('Content-Length: '.strlen($output));

				//Display the pdf
				echo $output;

				//Die for now
				exit;

#				//Create message
#				$message = (new TemplatedEmail())
#					//Set sender
#					->from(new Address($data['mail'], $data['name']))
#					//Set recipient
#					//XXX: remove the debug set in vendor/symfony/mime/Address.php +46
#					->to(new Address($this->config['contact']['mail'], $this->config['contact']['title']))
#					//Set subject
#					->subject($data['subject'])
#
#					//Set path to twig templates
#					->htmlTemplate('@RapsysAir/mail/contact.html.twig')
#					->textTemplate('@RapsysAir/mail/contact.text.twig')
#
#					//Set context
#					->context(
#						[
#							'subject' => $data['subject'],
#							'message' => strip_tags($data['message']),
#						]+$this->context
#					);
#
#				//Try sending message
#				//XXX: mail delivery may silently fail
#				try {
#					//Send message
#					$mailer->send($message);
#
#					//Redirect on the same route with sent=1 to cleanup form
#					return $this->redirectToRoute($request->get('_route'), ['sent' => 1]+$request->get('_route_params'));
#				//Catch obvious transport exception
#				} catch(TransportExceptionInterface $e) {
#					if ($message = $e->getMessage()) {
#						//Add error message mail unreachable
#						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%: %message%', ['%mail%' => $this->config['contact']['mail'], '%message%' => $this->translator->trans($message)])));
#					} else {
#						//Add error message mail unreachable
#						$form->get('mail')->addError(new FormError($this->translator->trans('Unable to contact: %mail%', ['%mail%' => $this->config['contact']['mail']])));
#					}
#				}
			}
		}

		//Render template
		return $this->render('@RapsysAir/default/dispute.html.twig', ['form' => $form->createView(), 'sent' => $request->query->get('sent', 0)]+$this->context);
	}

	/**
	 * The index page
	 *
	 * @desc Display all granted sessions with an application or login form
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set page
		$this->context['title'] = $this->translator->trans('Argentine Tango in Paris');

		//Set description
		$this->context['description'] = $this->translator->trans('Outdoor Argentine Tango session calendar in Paris');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('Paris'),
			$this->translator->trans('outdoor'),
			$this->translator->trans('calendar'),
			$this->translator->trans('Libre Air')
		];

		//Set facebook type
		//XXX: only valid for home page
		$this->context['facebook']['metas']['og:type'] = 'website';

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 3 week':'Monday this week + 2 week'
			)
		);

		//Fetch calendar
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'), $request->getLocale());

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/default/index.html.twig', ['calendar' => $calendar, 'locations' => $locations]+$this->context);

		//Set Cache-Control must-revalidate directive
		//TODO: add a javascript forced refresh after 1h ? or header refresh ?
		#$response->setPublic(true);
		#$response->setMaxAge(300);
		#$response->mustRevalidate();
		##$response->setCache(['public' => true, 'max_age' => 300]);

		//Return the response
		#return $response;
	}

	/**
	 * The organizer regulation page
	 *
	 * @desc Display the organizer regulation policy
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function organizerRegulation(Request $request): Response {
		//Set page
		$this->context['title'] = $this->translator->trans('Organizer regulation');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air organizer regulation');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('organizer regulation'),
			$this->translator->trans('Libre Air')
		];

		//Render template
		$response = $this->render('@RapsysAir/default/organizer_regulation.html.twig', $this->context);

		//Set as cachable
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($request);

		//Return response
		return $response;
	}

	/**
	 * The terms of service page
	 *
	 * @desc Display the terms of service policy
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function termsOfService(Request $request): Response {
		//Set page
		$this->context['title'] = $this->translator->trans('Terms of service');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air terms of service');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('terms of service'),
			$this->translator->trans('Libre Air')
		];

		//Render template
		$response = $this->render('@RapsysAir/default/terms_of_service.html.twig', $this->context);

		//Set as cachable
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($request);

		//Return response
		return $response;
	}

	/**
	 * The frequently asked questions page
	 *
	 * @desc Display the frequently asked questions
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function frequentlyAskedQuestions(Request $request): Response {
		//Set page
		$this->context['title'] = $this->translator->trans('Frequently asked questions');

		//Set description
		$this->context['description'] = $this->translator->trans('Libre Air frequently asked questions');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('frequently asked questions'),
			$this->translator->trans('faq'),
			$this->translator->trans('Libre Air')
		];

		//Render template
		$response = $this->render('@RapsysAir/default/frequently_asked_questions.html.twig', $this->context);

		//Set as cachable
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($request);

		//Return response
		return $response;
	}

	/**
	 * List all users
	 *
	 * @desc Display all user with a group listed as users
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view
	 */
	public function userIndex(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//With admin role
		if ($this->isGranted('ROLE_ADMIN')) {
			//Set section
			$section = $this->translator->trans('Libre Air users');

			//Set description
			$this->context['description'] = $this->translator->trans('Libre Air user list');
		//Without admin role
		} else {
			//Set section
			$section = $this->translator->trans('Libre Air organizers');

			//Set description
			$this->context['description'] = $this->translator->trans('Libre Air organizers list');
		}

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('users'),
			$this->translator->trans('user list'),
			$this->translator->trans('listing'),
			$this->translator->trans('Libre Air')
		];

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Fetch users
		$users = $doctrine->getRepository(User::class)->findUserGroupedByTranslatedGroup($this->translator);

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 3 week':'Monday this week + 2 week'
			)
		);

		//With admin role
		if ($this->isGranted('ROLE_ADMIN')) {
			//Display all users
			$this->context['groups'] = $users;
		//Without admin role
		} else {
			//Only display senior organizers
			$this->context['users'] = $users[$this->translator->trans('Senior')];
		}

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period);

		//Render the view
		return $this->render('@RapsysAir/user/index.html.twig', ['title' => $title, 'section' => $section, 'locations' => $locations]+$this->context);
	}

	/**
	 * List all sessions for the user
	 *
	 * @desc Display all sessions for the user with an application or login form
	 *
	 * @param Request $request The request instance
	 * @param int $id The user id
	 *
	 * @return Response The rendered view
	 */
	public function userView(Request $request, $id): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Fetch user
		if (empty($user = $doctrine->getRepository(User::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find user: %id%', ['%id%' => $id]));
		}

		//Get user token
		$token = new UsernamePasswordToken($user, null, 'none', $user->getRoles());

		//Check if guest
		$isGuest = $this->get('rapsys_user.access_decision_manager')->decide($token, ['ROLE_GUEST']);

		//Prevent access when not admin, user is not guest and not currently logged user
		if (!$this->isGranted('ROLE_ADMIN') && empty($isGuest) && $user != $this->getUser()) {
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access user: %id%', ['%id%' => $id]));
		}

		//Set section
		$section = $user->getPseudonym();

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Set description
		$this->context['description'] = $this->translator->trans('%pseudonym% outdoor Argentine Tango session calendar', [ '%pseudonym%' => $user->getPseudonym() ]);

		//Set keywords
		$this->context['keywords'] = [
			$user->getPseudonym(),
			$this->translator->trans('outdoor'),
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('calendar')
		];

		//Compute period
		$period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			new \DateTime(
				$this->isGranted('IS_AUTHENTICATED_REMEMBERED')?'Monday this week + 3 week':'Monday this week + 2 week'
			)
		);

		//Fetch calendar
		//TODO: highlight with current session route parameter
		$calendar = $doctrine->getRepository(Session::class)->fetchUserCalendarByDatePeriod($this->translator, $period, $isGuest?$id:null, $request->get('session'), $request->getLocale());

		//Fetch locations
		//XXX: we want to display all active locations anyway
		$locations = $doctrine->getRepository(Location::class)->findTranslatedSortedByPeriod($this->translator, $period, $id);

		//Create user form for admin or current user
		if ($this->isGranted('ROLE_ADMIN') || $user == $this->getUser()) {
			//Create SnippetType form
			$userForm = $this->createForm('Rapsys\AirBundle\Form\RegisterType', $user, [
				//Set action
				'action' => $this->generateUrl('rapsys_air_user_view', ['id' => $id]),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ],
				//Set civility class
				'civility_class' => Civility::class,
				//Disable mail
				'mail' => $this->isGranted('ROLE_ADMIN'),
				//Disable password
				'password' => false
			]);

			//Init user to context
			$this->context['forms']['user'] = $userForm->createView();

			//Check if submitted
			if ($request->isMethod('POST')) {
				//Refill the fields in case the form is not valid.
				$userForm->handleRequest($request);

				//Handle invalid form
				if (!$userForm->isSubmitted() || !$userForm->isValid()) {
					//Render the view
					return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
				}

				//Get data
				$data = $userForm->getData();

				//Get manager
				$manager = $doctrine->getManager();

				//Queue snippet save
				$manager->persist($data);

				//Flush to get the ids
				$manager->flush();

				//Add notice
				$this->addFlash('notice', $this->translator->trans('User %id% updated', ['%id%' => $id]));

				//Extract and process referer
				if ($referer = $request->headers->get('referer')) {
					//Create referer request instance
					$req = Request::create($referer);

					//Get referer path
					$path = $req->getPathInfo();

					//Get referer query string
					$query = $req->getQueryString();

					//Remove script name
					$path = str_replace($request->getScriptName(), '', $path);

					//Try with referer path
					try {
						//Save old context
						$oldContext = $this->router->getContext();

						//Force clean context
						//XXX: prevent MethodNotAllowedException because current context method is POST in onevendor/symfony/routing/Matcher/Dumper/CompiledUrlMatcherTrait.php+42
						$this->router->setContext(new RequestContext());

						//Retrieve route matching path
						$route = $this->router->match($path);

						//Reset context
						$this->router->setContext($oldContext);

						//Clear old context
						unset($oldContext);

						//Extract name
						$name = $route['_route'];

						//Remove route and controller from route defaults
						unset($route['_route'], $route['_controller']);

						//Check if user view route
						if ($name == 'rapsys_air_user_view' && !empty($route['id'])) {
							//Replace id
							$route['id'] = $data->getId();
						//Other routes
						} else {
							//Set user
							$route['user'] = $data->getId();
						}

						//Generate url
						return $this->redirectToRoute($name, $route);
					//No route matched
					} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
						//Unset referer to fallback to default route
						unset($referer);
					}
				}

				//Redirect to cleanup the form
				return $this->redirectToRoute('rapsys_air', ['user' => $data->getId()]);
			}
		}

		//Create snippet forms for role_guest
		if ($this->isGranted('ROLE_ADMIN') || ($this->isGranted('ROLE_GUEST') && $user == $this->getUser())) {
			//Fetch all user snippet
			$snippets = $doctrine->getRepository(Snippet::class)->findByLocaleUserId($request->getLocale(), $id);

			//Rekey by location id
			$snippets = array_reduce($snippets, function($carry, $item){$carry[$item->getLocation()->getId()] = $item; return $carry;}, []);

			//Init snippets to context
			$this->context['forms']['snippets'] = [];

			//Iterate on locations
			foreach($locations as $locationId => $location) {
				//Init snippet
				$snippet = new Snippet();

				//Set default locale
				$snippet->setLocale($request->getLocale());

				//Set default user
				$snippet->setUser($user);

				//Set default location
				$snippet->setLocation($doctrine->getRepository(Location::class)->findOneById($locationId));

				//With existing snippet
				if (!empty($snippets[$locationId])) {
					$snippet = $snippets[$locationId];
					$action = $this->generateUrl('rapsys_air_snippet_edit', ['id' => $snippet->getId()]);
				//Without snippet
				} else {
					$action = $this->generateUrl('rapsys_air_snippet_add', ['location' => $locationId]);
				}

				//Create SnippetType form
				$form = $this->container->get('form.factory')->createNamed('snipped_'.$request->getLocale().'_'.$locationId, 'Rapsys\AirBundle\Form\SnippetType', $snippet, [
					//Set the action
					'action' => $action,
					//Set the form attribute
					'attr' => []
				]);

				//Add form to context
				$this->context['forms']['snippets'][$locationId] = $form->createView();
			}
		}

		//Render the view
		return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'calendar' => $calendar, 'locations' => $locations]+$this->context);
	}
}
