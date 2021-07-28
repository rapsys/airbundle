<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Rapsys\AirBundle\Entity\Application;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Pdf\DisputePdf;
use Rapsys\UserBundle\Utils\Slugger;


class DefaultController {
	use ControllerTrait {
		//Rename render as _render
		render as protected _render;
	}

	///Config array
	protected $config;

	///Context array
	protected $context;

	///Router instance
	protected $router;

	///Translator instance
	protected $translator;

	///Packages instance
	protected $asset;

	///RequestStack instance
	protected $stack;

	///Request instance
	protected $request;

	///Locale instance
	protected $locale;

	/**
	 * @var ContainerInterface
	 */
	protected $container;

	///Facebook image array
	protected $facebookImage = [];

	/**
	 * Inject container and translator interface
	 *
	 * @param ContainerInterface $container The container instance
	 * @param RouterInterface $router The router instance
	 * @param RequestStack $stack The request stack
	 * @param TranslatorInterface $translator The translator instance
	 */
	public function __construct(ContainerInterface $container, RouterInterface $router, RequestStack $stack, TranslatorInterface $translator, Packages $asset) {
		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Set the container
		$this->container = $container;

		//Set the router
		$this->router = $router;

		//Set the translator
		$this->translator = $translator;

		//Set the asset
		$this->asset = $asset;

		//Set the request stack
		$this->stack = $stack;

		//Set the context
		$this->context = [
			'contact' => [
				'title' => $translator->trans($this->config['contact']['title']),
				'mail' => $this->config['contact']['mail']
			],
			'copy' => [
				'by' => $translator->trans($this->config['copy']['by']),
				'link' => $this->config['copy']['link'],
				'long' => $translator->trans($this->config['copy']['long']),
				'short' => $translator->trans($this->config['copy']['short']),
				'title' => $this->config['copy']['title']
			],
			'page' => [
				'description' => null,
				'section' => null,
				'title' => null
			],
			'site' => [
				'donate' => $this->config['site']['donate'],
				'ico' => $this->config['site']['ico'],
				'logo' => $this->config['site']['logo'],
				'png' => $this->config['site']['png'],
				'svg' => $this->config['site']['svg'],
				'title' => $translator->trans($this->config['site']['title']),
				'url' => $router->generate($this->config['site']['url'])
			],
			'canonical' => null,
			'alternates' => [],
			'ogps' => [
				'type' => 'article',
				'site_name' => $this->translator->trans($this->config['site']['title'])
			],
			'facebooks' => [
				#'admins' => $this->config['facebook']['admins'],
				'app_id' => $this->config['facebook']['apps']
			],
			'forms' => []
		];

		//Get current request
		$this->request = $stack->getCurrentRequest();

		//Get current locale
		#$this->locale = $router->getContext()->getParameters()['_locale'];
		$this->locale = $this->request->getLocale();

		//Set translator locale
		//XXX: allow LocaleSubscriber on the fly locale change for first page
		$this->translator->setLocale($this->locale);

		//Iterate on locales excluding current one
		foreach($this->config['locales'] as $locale) {
			//Set titles
			$titles = [];

			//Iterate on other locales
			foreach(array_diff($this->config['locales'], [$locale]) as $other) {
				$titles[$other] = $translator->trans($this->config['languages'][$locale], [], null, $other);
			}

			//Get context path
			$path = $router->getContext()->getPathInfo();

			//Retrieve route matching path
			$route = $router->match($path);

			//Get route name
			$name = $route['_route'];

			//Unset route name
			unset($route['_route']);

			//With current locale
			if ($locale == $this->locale) {
				//Set locale locales context
				$this->context['canonical'] = $router->generate($name, ['_locale' => $locale]+$route, UrlGeneratorInterface::ABSOLUTE_URL);
			} else {
				//Set locale locales context
				$this->context['alternates'][$locale] = [
					'absolute' => $router->generate($name, ['_locale' => $locale]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $locale]+$route),
					'title' => implode('/', $titles),
					'translated' => $translator->trans($this->config['languages'][$locale], [], null, $locale)
				];
			}

			//Add shorter locale
			if (empty($this->context['alternates'][$shortLocale = substr($locale, 0, 2)])) {
				//Set locale locales context
				$this->context['alternates'][$shortLocale] = [
					'absolute' => $router->generate($name, ['_locale' => $locale]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $locale]+$route),
					'title' => implode('/', $titles),
					'translated' => $translator->trans($this->config['languages'][$locale], [], null, $locale)
				];
			}
		}
	}

	/**
	 * The about page
	 *
	 * @desc Display the about informations
	 *
	 * @return Response The rendered view
	 */
	public function about(): Response {
		//Set page
		$this->context['page']['title'] = $this->translator->trans('About');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Libre Air about');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('about'),
			$this->translator->trans('Libre Air')
		];

		//Render template
		$response = $this->render('@RapsysAir/default/about.html.twig', $this->context);
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($this->request);

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
		$this->context['page']['title'] = $this->translator->trans('Contact');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Contact Libre Air');

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
		$this->context['page']['title'] = $this->translator->trans('Dispute');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Libre Air dispute');

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
	 *
	 * @return Response The rendered view
	 */
	public function index(Request $request): Response {
		//Fetch doctrine
		$doctrine = $this->getDoctrine();

		//Set page
		$this->context['page']['title'] = $this->translator->trans('Argentine Tango in Paris');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Outdoor Argentine Tango session calendar in Paris');

		//Set keywords
		$this->context['keywords'] = [
			$this->translator->trans('Argentine Tango'),
			$this->translator->trans('Paris'),
			$this->translator->trans('outdoor'),
			$this->translator->trans('calendar'),
			$this->translator->trans('Libre Air')
		];

		//Set type
		//XXX: only valid for home page
		$this->context['ogps']['type'] = 'website';

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
		$calendar = $doctrine->getRepository(Session::class)->fetchCalendarByDatePeriod($this->translator, $period, null, $request->get('session'), !$this->isGranted('IS_AUTHENTICATED_REMEMBERED'));

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
	 * @return Response The rendered view
	 */
	public function organizerRegulation(): Response {
		//Set page
		$this->context['page']['title'] = $this->translator->trans('Organizer regulation');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Libre Air organizer regulation');

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
		$response->isNotModified($this->request);

		//Return response
		return $response;
	}

	/**
	 * The terms of service page
	 *
	 * @desc Display the terms of service policy
	 *
	 * @return Response The rendered view
	 */
	public function termsOfService(): Response {
		//Set page
		$this->context['page']['title'] = $this->translator->trans('Terms of service');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Libre Air terms of service');

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
		$response->isNotModified($this->request);

		//Return response
		return $response;
	}

	/**
	 * The frequently asked questions page
	 *
	 * @desc Display the frequently asked questions
	 *
	 * @return Response The rendered view
	 */
	public function frequentlyAskedQuestions(): Response {
		//Set page
		$this->context['page']['title'] = $this->translator->trans('Frequently asked questions');

		//Set description
		$this->context['page']['description'] = $this->translator->trans('Libre Air frequently asked questions');

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
		$response->isNotModified($this->request);

		//Return response
		return $response;
	}

	/**
	 * Return the bundle alias
	 *
	 * {@inheritdoc}
	 */
	public function getAlias(): string {
		return 'rapsys_air';
	}

	/**
	 * Return the facebook image
	 *
	 * @desc Generate image in jpeg format or load it from cache
	 *
	 * @return array The image array
	 */
	protected function getFacebookImage(): array {
		//Set texts
		$texts = $this->facebookImage['texts'] ?? [];

		//Set default source
		$source = $this->facebookImage['source'] ?? 'png/facebook.png';

		//Set default source
		$updated = $this->facebookImage['updated'] ?? strtotime('last week');

		//Set default destination
		//XXX: format facebook<pathinfo>.jpeg
		//XXX: was facebook/<controller>/<action>.<locale>.jpeg
		$destination = $this->facebookImage['destination'] ?? 'facebook'.$this->request->getPathInfo().'.jpeg';

		//Set source path
		$src = $this->config['path']['public'].'/'.$source;

		//Set cache path
		//XXX: remove extension and store as png anyway
		$cache = $this->config['path']['cache'].'/facebook/'.substr($source, 0, strrpos($source, '.')).'.'.$this->config['facebook']['width'].'x'.$this->config['facebook']['height'].'.png';

		//Set destination path
		$dest = $this->config['path']['public'].'/'.$destination;

		//Set asset
		$asset = '@RapsysAir/'.$destination;

		//With up to date generated image
		if (
			is_file($dest) &&
			($stat = stat($dest)) &&
			$stat['mtime'] >= $updated
		) {
			//Get image size
			list ($width, $height) = getimagesize($dest);

			//With canonical in texts
			if (!empty($texts[$this->context['canonical']])) {
				//Prevent canonical to finish in alt
				unset($texts[$this->context['canonical']]);
			}

			//Return image data
			return [
				#'image' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				'image:url' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				#'image:secure_url' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				'image:alt' => str_replace("\n", ' ', implode(' - ', array_keys($texts))),
				'image:height' => $height,
				'image:width' => $width
			];
		//With image candidate
		} elseif (is_file($src)) {
			//Create image object
			$image = new \Imagick();

			//With cache image
			if (is_file($cache)) {
				//Read image
				$image->readImage($cache);
			//Without we generate it
			} else {
				//Check target directory
				if (!is_dir($dir = dirname($cache))) {
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

				//Read image
				$image->readImage($src);

				//Crop using aspect ratio
				//XXX: for better result upload image directly in aspect ratio :)
				$image->cropThumbnailImage($this->config['facebook']['width'], $this->config['facebook']['height']);

				//Strip image exif data and properties
				$image->stripImage();

				//Save cache image
				if (!$image->writeImage($cache)) {
					//Throw error
					throw new \Exception(sprintf('Unable to write image "%s"', $cache));
				}
			}
			//Check target directory
			if (!is_dir($dir = dirname($dest))) {
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

			//Get image width
			$width = $image->getImageWidth();

			//Get image height
			$height = $image->getImageHeight();

			//Create draw
			$draw = new \ImagickDraw();

			//Set stroke antialias
			$draw->setStrokeAntialias(true);

			//Set text antialias
			$draw->setTextAntialias(true);

			//Set stroke width
			$draw->setStrokeWidth($this->facebookImage['stroke']??15);

			//Set font aliases
			$fonts = [
				'irishgrover' => $this->config['path']['public'].'/ttf/irishgrover.v10.ttf',
				'droidsans' => $this->config['path']['public'].'/ttf/droidsans.regular.ttf',
				'dejavusans' => $this->config['path']['public'].'/ttf/dejavusans.2.37.ttf',
				'labelleaurore' => $this->config['path']['public'].'/ttf/labelleaurore.v10.ttf'
			];

			//Set align aliases
			$aligns = [
				'left' => \Imagick::ALIGN_LEFT,
				'center' => \Imagick::ALIGN_CENTER,
				'right' => \Imagick::ALIGN_RIGHT
			];

			//Set default font
			$defaultFont = 'dejavusans';

			//Set default align
			$defaultAlign = 'center';

			//Set default size
			$defaultSize = 60;

			//Set default stroke
			$defaultStroke = '#00c3f9';

			//Set default fill
			$defaultFill = 'white';

			//Init counter
			$i = 1;

			//Set text count
			$count = count($texts);

			//Draw each text stroke
			foreach($texts as $text => $data) {
				//Set font
				$draw->setFont($fonts[$data['font']??$defaultFont]);

				//Set font size
				$draw->setFontSize($data['size']??$defaultSize);

				//Set text alignment
				$draw->setTextAlignment($align = ($aligns[$data['align']??$defaultAlign]));

				//Get font metrics
				$metrics = $image->queryFontMetrics($draw, $text);

				//Without y
				if (empty($data['y'])) {
					//Position verticaly each text evenly
					$texts[$text]['y'] = $data['y'] = (($height + 100) / (count($texts) + 1) * $i) - 50;
				}

				//Without x
				if (empty($data['x'])) {
					if ($align == \Imagick::ALIGN_CENTER) {
						$texts[$text]['x'] = $data['x'] = $width/2;
					} elseif ($align == \Imagick::ALIGN_LEFT) {
						$texts[$text]['x'] = $data['x'] = 50;
					} elseif ($align == \Imagick::ALIGN_RIGHT) {
						$texts[$text]['x'] = $data['x'] = $width - 50;
					}
				}

				//Center verticaly
				//XXX: add ascender part then center it back by half of textHeight
				//TODO: maybe add a boundingbox ???
				$texts[$text]['y'] = $data['y'] += $metrics['ascender'] - $metrics['textHeight']/2;

				//Set stroke color
				$draw->setStrokeColor(new \ImagickPixel($data['stroke']??$defaultStroke));

				//Set fill color
				$draw->setFillColor(new \ImagickPixel($data['stroke']??$defaultStroke));

				//Add annotation
				$draw->annotation($data['x'], $data['y'], $text);

				//Increase counter
				$i++;
			}

			//Create stroke object
			$stroke = new \Imagick();

			//Add new image
			$stroke->newImage($width, $height, new \ImagickPixel('transparent'));

			//Draw on image
			$stroke->drawImage($draw);

			//Blur image
			//XXX: blur the stroke canvas only
			$stroke->blurImage(5,3);

			//Set opacity to 0.5
			//XXX: see https://www.php.net/manual/en/image.evaluateimage.php
			$stroke->evaluateImage(\Imagick::EVALUATE_DIVIDE, 1.5, \Imagick::CHANNEL_ALPHA);

			//Compose image
			$image->compositeImage($stroke, \Imagick::COMPOSITE_OVER, 0, 0);

			//Clear stroke
			$stroke->clear();

			//Destroy stroke
			unset($stroke);

			//Clear draw
			$draw->clear();

			//Set text antialias
			$draw->setTextAntialias(true);

			//Draw each text
			foreach($texts as $text => $data) {
				//Set font
				$draw->setFont($fonts[$data['font']??$defaultFont]);

				//Set font size
				$draw->setFontSize($data['size']??$defaultSize);

				//Set text alignment
				$draw->setTextAlignment($aligns[$data['align']??$defaultAlign]);

				//Set fill color
				$draw->setFillColor(new \ImagickPixel($data['fill']??$defaultFill));

				//Add annotation
				$draw->annotation($data['x'], $data['y'], $text);
			}

			//Draw on image
			$image->drawImage($draw);

			//Strip image exif data and properties
			$image->stripImage();

			//Set image format
			$image->setImageFormat('jpeg');

			//Save image
			if (!$image->writeImage($dest)) {
				//Throw error
				throw new \Exception(sprintf('Unable to write image "%s"', $dest));
			}

			//Get dest stat
			//TODO: see if it works every time
			$stat = stat($dest);

			//With canonical in texts
			if (!empty($texts[$this->context['canonical']])) {
				//Prevent canonical to finish in alt
				unset($texts[$this->context['canonical']]);
			}

			//Return image data
			return [
				//TODO: see if it works every time
				#'image' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				'image:url' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				#'image:secure_url' => $this->stack->getCurrentRequest()->getUriForPath($this->asset->getUrl($asset), true),#.'?fbrefresh='.$stat['mtime'],
				'image:alt' => str_replace("\n", ' ', implode(' - ', array_keys($texts))),
				'image:height' => $height,
				'image:width' => $width
			];
		}

		//Return empty array without image
		return [];
	}

	/**
	 * Renders a view
	 *
	 * {@inheritdoc}
	 */
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Without application form
			if (empty($parameters['forms']['application'])) {
				//Fetch doctrine
				$doctrine = $this->getDoctrine();

				//Create ApplicationType form
				$application = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
					//Set the action
					'action' => $this->generateUrl('rapsys_air_application_add'),
					//Set the form attribute
					'attr' => [ 'class' => 'col' ],
					//Set admin
					'admin' => $this->isGranted('ROLE_ADMIN'),
					//Set default user to current
					'user' => $this->getUser()->getId(),
					//Set default slot to evening
					//XXX: default to Evening (3)
					'slot' => $doctrine->getRepository(Slot::class)->findOneById(3)
				]);

				//Add form to context
				$parameters['forms']['application'] = $application->createView();
			}
		//Create login form for anonymous
		} elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create ApplicationType form
			$login = $this->createForm('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_user_login'),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$parameters['forms']['login'] = $login->createView();
		}

		//With page infos and without facebook image
		if (empty($this->facebookImage) && !empty($parameters['site']['title']) && !empty($parameters['page']['title']) && !empty($parameters['canonical'])) {
			//Set facebook image
			$this->facebookImage = [
				'texts' => [
					$parameters['site']['title'] => [
						'font' => 'irishgrover',
						'size' => 110
					],
					$parameters['page']['title'] => [
						'align' => 'left'
					],
					$parameters['canonical'] => [
						'align' => 'right',
						'font' => 'labelleaurore',
						'size' => 50
					]
				]
			];
		}

		//With canonical
		if (!empty($parameters['canonical'])) {
			//Set facebook url
			$parameters['ogps']['url'] = $parameters['canonical'];
		}

		//With page title
		if (!empty($parameters['page']['title'])) {
			//Set facebook title
			$parameters['ogps']['title'] = $parameters['page']['title'];
		}

		//With page description
		if (!empty($parameters['page']['description'])) {
			//Set facebook description
			$parameters['ogps']['description'] = $parameters['page']['description'];
		}

		//With locale
		if (!empty($this->locale)) {
			//Set facebook locale
			$parameters['ogps']['locale'] = str_replace('-', '_', $this->locale);

			//With alternates
			//XXX: disabled as we don't support fb_locale=xx_xx
			//XXX: see https://stackoverflow.com/questions/20827882/in-open-graph-markup-whats-the-use-of-oglocalealternate-without-the-locati
			#if (!empty($parameters['alternates'])) {
			#	//Iterate on alternates
			#	foreach($parameters['alternates'] as $lang => $alternate) {
			#		if (strlen($lang) == 5) {
			#			//Set facebook locale alternate
			#			$parameters['ogps']['locale:alternate'] = str_replace('-', '_', $lang);
			#		}
			#	}
			#}
		}

		//With facebook image defined
		if (!empty($this->facebookImage)) {
			//Get facebook image
			$parameters['ogps'] += $this->getFacebookImage();
		}

		//Call parent method
		return $this->_render($view, $parameters, $response);
	}
}
