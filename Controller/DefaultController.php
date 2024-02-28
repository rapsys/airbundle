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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\AirBundle\Entity\User;

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
	 *
	 * @return Response The rendered view or redirection
	 */
	public function contact(Request $request): Response {
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

		//Set data
		$data = [];

		//With user
		if ($user = $this->getUser()) {
			//Set data
			$data = [
				'name' => $user->getRecipientName(),
				'mail' => $user->getMail()
			];
		}

		//Create the form according to the FormType created previously.
		//And give the proper parameters
		$form = $this->createForm('Rapsys\AirBundle\Form\ContactType', $data, [
			'action' => $this->generateUrl('rapsys_air_contact'),
			'method' => 'POST'
		]);

		if ($request->isMethod('POST')) {
			// Refill the fields in case the form is not valid.
			$form->handleRequest($request);

			if ($form->isSubmitted() && $form->isValid()) {
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
					$this->mailer->send($message);

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
	 * The index page
	 *
	 * Display session calendar
	 *
	 * @param Request $request The request instance
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Add cities
		$this->context['cities'] = $this->doctrine->getRepository(Location::class)->findCitiesAsArray($this->period);

		//Add calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period, !$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED'));

		//Add dances
		$this->context['dances'] = $this->doctrine->getRepository(Dance::class)->findNamesAsArray();

		//Set modified
		$this->modified = max(array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['cities'], $this->context['dances'])));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['calendar'], $this->context['cities'], $this->context['dances']))));

			//Set last modified
			$response->setLastModified($this->modified);

			//Set as public
			$response->setPublic();

			//Without role and modification
			if ($response->isNotModified($request)) {
				//Return 304 response
				return $response;
			}
		}

		//With cities
		if (!empty($this->context['cities'])) {
			//Set locations
			$locations = [];

			//Iterate on each cities
			foreach($this->context['cities'] as $city) {
				//Iterate on each locations
				foreach($city['locations'] as $location) {
					//Add location
					$locations[$location['id']] = $location;
				}
			}

			//Add multi
			$this->context['multimap'] = $this->map->getMultiMap($this->translator->trans('Libre Air cities sector map'), $this->modified->getTimestamp(), $locations);

			//Set cities
			$cities = array_map(function ($v) { return $v['in']; }, $this->context['cities']);

			//Set dances
			$dances = array_map(function ($v) { return $v['name']; }, $this->context['dances']);
		} else {
			//Set cities
			$cities = [];

			//Set dances
			$dances = [];
		}

		//Set keywords
		//TODO: use splice instead of that shit !!!
		//TODO: handle smartly indoor and outdoor !!!
		$this->context['keywords'] = array_values(
			array_merge(
				$dances,
				$cities,
				[
					$this->translator->trans('indoor'),
					$this->translator->trans('outdoor'),
					$this->translator->trans('calendar'),
					$this->translator->trans('Libre Air')
				]
			)
		);

		//Get textual cities
		$cities = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($cities, 0, -1))], array_slice($cities, -1)), 'strlen'));

		//Get textual dances
		$dances = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($dances, 0, -1))], array_slice($dances, -1)), 'strlen'));

		//Set title
		$this->context['title'] = $this->translator->trans('%dances% %cities%', ['%dances%' => $dances, '%cities%' => $cities]);

		//Set description
		//TODO: handle french translation when city start with a A, change à in en !
		$this->context['description'] = $this->translator->trans('%dances% indoor and outdoor calendar %cities%', ['%dances%' => $dances, '%cities%' => $cities]);

		//Set facebook type
		//XXX: only valid for home page
		$this->context['facebook']['metas']['og:type'] = 'website';

		//Render the view
		return $this->render('@RapsysAir/default/index.html.twig', $this->context, $response);
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
		//With admin role
		if ($this->checker->isGranted('ROLE_ADMIN')) {
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
		$users = $this->doctrine->getRepository(User::class)->findIndexByGroupId();

		//With admin role
		if ($this->checker->isGranted('ROLE_ADMIN')) {
			//Display all users
			$this->context['groups'] = $users;
		//Without admin role
		} else {
			//Only display senior organizers
			$this->context['users'] = $users[$this->translator->trans('Senior')];
		}

		//Render the view
		return $this->render('@RapsysAir/user/index.html.twig', ['title' => $title, 'section' => $section]+$this->context);
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
	public function userView(Request $request, int $id, ?string $user): Response {
		//Get user
		if (empty($this->context['user'] = $this->doctrine->getRepository(User::class)->findOneByIdAsArray($id, $this->locale))) {
			//Throw not found
			throw new NotFoundHttpException($this->translator->trans('Unable to find user: %id%', ['%id%' => $id]));
		}

		//Create token
		$token = new AnonymousToken('', $this->context['user']['mail'], $this->context['user']['roles']);

		//Prevent access when not admin, user is not guest and not currently logged user
		if (!($isAdmin = $this->checker->isGranted('ROLE_ADMIN')) && !($isGuest = $this->decision->decide($token, ['ROLE_GUEST']))) {
			//Throw access denied
			throw new AccessDeniedException($this->translator->trans('Unable to access user: %id%', ['%id%' => $id]));
		}

		//With invalid user slug
		if ($this->context['user']['slug'] !== $user) {
			//Redirect to cleaned url
			return $this->redirectToRoute('rapsys_air_user_view', ['id' => $id, 'user' => $this->context['user']['slug']]);
		}

		//Fetch calendar
		$this->context['calendar'] = $this->doctrine->getRepository(Session::class)->findAllByPeriodAsCalendarArray($this->period, !$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED'), null, null, $id);

		//Get locations at less than 2 km
		$this->context['locations'] = $this->doctrine->getRepository(Location::class)->findAllByUserIdAsArray($id, $this->period, 2);

		//Set ats
		$ats = [];

		//Set dances
		$dances = [];

		//Set indoors
		$indoors = [];

		//Set ins
		$ins = [];

		//Set insides
		$insides = [];

		//Set locations
		$locations = [];

		//Set types
		$types = [];

		//Iterate on each calendar
		foreach($this->context['calendar'] as $date => $calendar) {
			//Iterate on each session
			foreach($calendar['sessions'] as $sessionId => $session) {
				//Add dance
				$dances[$session['application']['dance']['name']] = $session['application']['dance']['name'];

				//Add types
				$types[$session['application']['dance']['type']] = lcfirst($session['application']['dance']['type']);

				//Add indoors
				$indoors[$session['location']['indoor']?'indoor':'outdoor'] = $this->translator->trans($session['location']['indoor']?'indoor':'outdoor');

				//Add insides
				$insides[$session['location']['indoor']?'inside':'outside'] = $this->translator->trans($session['location']['indoor']?'inside':'outside');

				//Add ats
				$ats[$session['location']['id']] = $session['location']['at'];

				//Add ins
				$ins[$session['location']['id']] = $session['location']['in'];

				//Session with application user id
				if (!empty($session['application']['user']['id']) && $session['application']['user']['id'] == $id) {
					//Add location
					$locations[$session['location']['id']] = $session['location'];
				}
			}
		}

		//Set modified
		//XXX: dance modified is already computed inside calendar modified
		$this->modified = max(array_merge([$this->context['user']['modified']], array_map(function ($v) { return $v['modified']; }, array_merge($this->context['calendar'], $this->context['locations']))));

		//Create response
		$response = new Response();

		//With logged user
		if ($this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Set last modified
			$response->setLastModified(new \DateTime('-1 year'));

			//Set as private
			$response->setPrivate();
		//Without logged user
		} else {
			//Set etag
			//XXX: only for public to force revalidation by last modified
			$response->setEtag(md5(serialize(array_merge($this->context['user'], $this->context['calendar'], $this->context['locations']))));

			//Set last modified
			$response->setLastModified($this->modified);

			//Set as public
			$response->setPublic();

			//Without role and modification
			if ($response->isNotModified($request)) {
				//Return 304 response
				return $response;
			}
		}

		//Add multi map
		$this->context['multimap'] = $this->map->getMultiMap($this->context['user']['multimap'], $this->modified->getTimestamp(), $this->context['locations']);

		//Set keywords
		$this->context['keywords'] = [
			$this->context['user']['pseudonym'],
			$this->translator->trans('calendar'),
			$this->translator->trans('Libre Air')
		];

		//Set cities
		$cities = array_unique(array_map(function ($v) { return $v['city']; }, $locations));

		//Set titles
		$titles = array_map(function ($v) { return $v['title']; }, $locations);

		//Insert dances in keywords
		array_splice($this->context['keywords'], 1, 0, array_merge($types, $dances, $indoors, $insides, $titles, $cities));

		//Deduplicate ins
		$ins = array_unique($ins);

		//Get textual dances
		$dances = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($dances, 0, -1))], array_slice($dances, -1)), 'strlen'));

		//Get textual types
		$types = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($types, 0, -1))], array_slice($types, -1)), 'strlen'));

		//Get textual indoors
		$indoors = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($indoors, 0, -1))], array_slice($indoors, -1)), 'strlen'));

		//Get textual ats
		$ats = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($ats, 0, -1))], array_slice($ats, -1)), 'strlen'));

		//Get textual ins
		$ins = implode($this->translator->trans(' and '), array_filter(array_merge([implode(', ', array_slice($ins, 0, -1))], array_slice($ins, -1)), 'strlen'));

		//Set title
		$this->context['title'] = $this->translator->trans('%pseudonym% organizer', ['%pseudonym%' => $this->context['user']['pseudonym']]);

		//With locations
		if (!empty($locations)) {
			//Set description
			$this->context['description'] = ucfirst($this->translator->trans('%dances% %types% %indoors% calendar %ats% %ins% %pseudonym%', ['%dances%' => $dances, '%types%' => $types, '%indoors%' => $indoors, '%ats%' => $ats, '%ins%' => $ins, '%pseudonym%' => $this->translator->trans('by %pseudonym%', ['%pseudonym%' => $this->context['user']['pseudonym']])]));
		//Without locations
		} else {
			//Set description
			$this->context['description'] = $this->translator->trans('%pseudonym% calendar', ['%pseudonym%' => $this->context['user']['pseudonym']]);
		}

		//Set user description
		$this->context['locations_description'] = $this->translator->trans('Libre Air %pseudonym% location list', ['%pseudonym%' => $this->translator->trans('by %pseudonym%', ['%pseudonym%' => $this->context['user']['pseudonym']])]);

		//Set alternates
		$this->context['alternates'] += $this->context['user']['alternates'];

		//Create snippet forms for role_guest
		//TODO: optimize this call
		if ($isAdmin || $isGuest && $this->getUser() && $this->context['user']['id'] == $this->getUser()->getId()) {
			//Fetch all user snippet
			$snippets = $this->doctrine->getRepository(Snippet::class)->findByUserIdLocaleIndexByLocationId($id, $this->locale);

			//Get user
			$user = $this->doctrine->getRepository(User::class)->findOneById($id);

			//Iterate on locations
			foreach($this->context['locations'] as $locationId => $location) {
				//With existing snippet
				if (isset($snippets[$location['id']])) {
					//Set existing in current
					$current = $snippets[$location['id']];
				//Without existing snippet
				} else {
					//Init snippet
					$current = new Snippet();

					//Set default locale
					$current->setLocale($this->locale);

					//Set default user
					$current->setUser($user);

					//Set default location
					$current->setLocation($this->doctrine->getRepository(Location::class)->findOneById($location['id']));
				}

				//Create SnippetType form
				$form = $this->factory->createNamed(
					//Set form id
					'snippet_'.$locationId.'_'.$id.'_'.$this->locale,
					//Set form type
					'Rapsys\AirBundle\Form\SnippetType',
					//Set form data
					$current
				);

				//Refill the fields in case of invalid form
				$form->handleRequest($request);

				//Handle submitted and valid form
				//TODO: add a delete snippet ?
				if ($form->isSubmitted() && $form->isValid()) {
					//Get snippet
					$snippet = $form->getData();

					//Queue snippet save
					$this->manager->persist($snippet);

					//Flush to get the ids
					$this->manager->flush();

					//Add notice
					$this->addFlash('notice', $this->translator->trans('Snippet for %user% %location% updated', ['%location%' => $location['at'], '%user%' => $this->context['user']['pseudonym']]));

					//Redirect to cleaned url
					return $this->redirectToRoute('rapsys_air_user_view', ['id' => $id, 'user' => $this->context['user']['slug']]);
				}

				//Add form to context
				$this->context['forms']['snippets'][$locationId] = $form->createView();

				//With location user source image
				if (($isFile = is_file($source = $this->config['path'].'/location/'.$location['id'].'/'.$id.'.png')) && ($mtime = stat($source)['mtime'])) {
					//Set location image
					$this->context['locations'][$locationId]['image'] = $this->image->getThumb($location['miniature'], $mtime, $source);
				}

				//Create ImageType form
				$form = $this->factory->createNamed(
					//Set form id
					'image_'.$locationId.'_'.$id,
					//Set form type
					'Rapsys\AirBundle\Form\ImageType',
					//Set form data
					[
						//Set location
						'location' => $location['id'],
						//Set user
						'user' => $id
					],
					//Set form attributes
					[
						//Enable delete with image
						'delete' => isset($this->context['locations'][$locationId]['image'])
					]
				);

				//Refill the fields in case of invalid form
				$form->handleRequest($request);

				//Handle submitted and valid form
				if ($form->isSubmitted() && $form->isValid()) {
					//With delete
					if ($form->has('delete') && $form->get('delete')->isClicked()) {
						//With source and mtime
						if ($isFile && !empty($source) && !empty($mtime)) {
							//Clear thumb
							$this->image->remove($mtime, $source);

							//Unlink file
							unlink($this->config['path'].'/location/'.$location['id'].'/'.$id.'.png');

							//Add notice
							$this->addFlash('notice', $this->translator->trans('Image for %user% %location% deleted', ['%location%' => $location['at'], '%user%' => $this->context['user']['pseudonym']]));

							//Redirect to cleaned url
							return $this->redirectToRoute('rapsys_air_user_view', ['id' => $id, 'user' => $this->context['user']['slug']]);
						}
					}

					//With image
					if ($image = $form->get('image')->getData()) {
						//Check source path
						if (!is_dir($dir = dirname($source))) {
							//Create filesystem object
							$filesystem = new Filesystem();

							try {
								//Create dir
								//XXX: set as 0775, symfony umask (0022) will reduce rights (0755)
								$filesystem->mkdir($dir, 0775);
							} catch (IOExceptionInterface $e) {
								//Throw error
								throw new \Exception(sprintf('Output directory "%s" do not exists and unable to create it', $dir), 0, $e);
							}
						}

						//Set source
						$source = realpath($dir).'/'.basename($source);

						//Create imagick object
						$imagick = new \Imagick();

						//Read image
						$imagick->readImage($image->getRealPath());

						//Save image
						if (!$imagick->writeImage($source)) {
							//Throw error
							throw new \Exception(sprintf('Unable to write image "%s"', $source));
						}

						//Add notice
						$this->addFlash('notice', $this->translator->trans('Image for %user% %location% updated', ['%location%' => $location['at'], '%user%' => $this->context['user']['pseudonym']]));

						//Redirect to cleaned url
						return $this->redirectToRoute('rapsys_air_user_view', ['id' => $id, 'user' => $this->context['user']['slug']]);
					}
				}

				//Add form to context
				$this->context['forms']['images'][$locationId] = $form->createView();
			}
		}

		//Render the view
		return $this->render('@RapsysAir/user/view.html.twig', ['id' => $id]+$this->context);
	}
}
