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

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\RapsysAirBundle;

/**
 * Provides common features needed in controllers.
 *
 * {@inheritdoc}
 */
abstract class AbstractController extends BaseAbstractController implements ServiceSubscriberInterface {
	use ControllerTrait {
		//Rename render as baseRender
		render as protected baseRender;
	}

	///Config array
	protected $config;

	///ContainerInterface instance
	protected $container;

	///Context array
	protected $context;

	///Router instance
	protected $router;

	///Translator instance
	protected $translator;

	/**
	 * Common constructor
	 *
	 * Stores container, router and translator interfaces
	 * Stores config
	 * Prepares context tree
	 *
	 * @param ContainerInterface $container The container instance
	 */
	public function __construct(ContainerInterface $container) {
		//Retrieve config
		$this->config = $container->getParameter(RapsysAirBundle::getAlias());

		//Set the container
		$this->container = $container;

		//Set the router
		$this->router = $container->get('router');

		//Set the translator
		$this->translator = $container->get('translator');

		//Set the context
		$this->context = [
			'description' => null,
			'section' => null,
			'title' => null,
			'contact' => [
				'title' => $this->translator->trans($this->config['contact']['title']),
				'mail' => $this->config['contact']['mail']
			],
			'copy' => [
				'by' => $this->translator->trans($this->config['copy']['by']),
				'link' => $this->config['copy']['link'],
				'long' => $this->translator->trans($this->config['copy']['long']),
				'short' => $this->translator->trans($this->config['copy']['short']),
				'title' => $this->config['copy']['title']
			],
			'site' => [
				'donate' => $this->config['site']['donate'],
				'ico' => $this->config['site']['ico'],
				'logo' => $this->config['site']['logo'],
				'png' => $this->config['site']['png'],
				'svg' => $this->config['site']['svg'],
				'title' => $this->translator->trans($this->config['site']['title']),
				'url' => $this->router->generate($this->config['site']['url'])
			],
			'canonical' => null,
			'alternates' => [],
			'facebook' => [
				'prefixes' => [
					'og' => 'http://ogp.me/ns#',
					'fb' => 'http://ogp.me/ns/fb#'
				],
				'metas' => [
					'og:type' => 'article',
					'og:site_name' => $this->translator->trans($this->config['site']['title']),
					#'fb:admins' => $this->config['facebook']['admins'],
					'fb:app_id' => $this->config['facebook']['apps']
				],
				'texts' => []
			],
			'forms' => []
		];
	}

	/**
	 * Return the facebook image
	 *
	 * @desc Generate image in jpeg format or load it from cache
	 *
	 * @param string $pathInfo The request path info
	 * @param array $parameters The image parameters
	 * @return array The image array
	 */
	protected function getFacebookImage(string $pathInfo, array $parameters = []): array {
		//Get asset package
		//XXX: require asset package to be public
		$package = $this->container->get('rapsys_pack.path_package');

		//Set texts
		$texts = $parameters['texts'] ?? [];

		//Set default source
		$source = $parameters['source'] ?? 'png/facebook.png';

		//Set default source
		$updated = $parameters['updated'] ?? strtotime('last week');

		//Set source path
		$src = $this->config['path']['public'].'/'.$source;

		//Set cache path
		//XXX: remove extension and store as png anyway
		$cache = $this->config['path']['cache'].'/facebook/'.substr($source, 0, strrpos($source, '.')).'.'.$this->config['facebook']['width'].'x'.$this->config['facebook']['height'].'.png';

		//Set destination path
		//XXX: format <public>/facebook<pathinfo>.jpeg
		//XXX: was <public>/facebook/<controller>/<action>.<locale>.jpeg
		$dest = $this->config['path']['public'].'/facebook'.$pathInfo.'.jpeg';

		//With up to date generated image
		if (
			is_file($dest) &&
			($stat = stat($dest)) &&
			$stat['mtime'] >= $updated
		) {
			//Get image size
			list ($width, $height) = getimagesize($dest);

			//Iterate each text
			foreach($texts as $text => $data) {
				//With canonical text
				if (!empty($data['canonical'])) {
					//Prevent canonical to finish in alt
					unset($texts[$text]);
				}
			}

			//Return image data
			return [
				'og:image' => $package->getAbsoluteUrl('@RapsysAir/facebook/'.$stat['mtime'].$pathInfo.'.jpeg'),
				'og:image:alt' => str_replace("\n", ' ', implode(' - ', array_keys($texts))),
				'og:image:height' => $height,
				'og:image:width' => $width
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

			//Set default width
			$defaultWidth = 15;

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

				//Set stroke width
				$draw->setStrokeWidth($data['width']??$defaultWidth);

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

				//With canonical text
				if (!empty($data['canonical'])) {
					//Prevent canonical to finish in alt
					unset($texts[$text]);
				}
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
			$stat = stat($dest);

			//Return image data
			return [
				'og:image' => $package->getAbsoluteUrl('@RapsysAir/facebook/'.$stat['mtime'].$pathInfo.'.jpeg'),
				'og:image:alt' => str_replace("\n", ' ', implode(' - ', array_keys($texts))),
				'og:image:height' => $height,
				'og:image:width' => $width
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
		//Get request stack
		$stack = $this->container->get('request_stack');

		//Get current request
		$request = $stack->getCurrentRequest();

		//Get current locale
		$locale = $request->getLocale();

		//Set locale
		$parameters['locale'] = str_replace('_', '-', $locale);

		//Get context path
		$pathInfo = $this->router->getContext()->getPathInfo();

		//Iterate on locales excluding current one
		foreach($this->config['locales'] as $current) {
			//Set titles
			$titles = [];

			//Iterate on other locales
			foreach(array_diff($this->config['locales'], [$current]) as $other) {
				$titles[$other] = $this->translator->trans($this->config['languages'][$current], [], null, $other);
			}

			//Retrieve route matching path
			$route = $this->router->match($pathInfo);

			//Get route name
			$name = $route['_route'];

			//Unset route name
			unset($route['_route']);

			//With current locale
			if ($current == $locale) {
				//Set locale locales context
				$parameters['canonical'] = $this->router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL);
			} else {
				//Set locale locales context
				$parameters['alternates'][str_replace('_', '-', $current)] = [
					'absolute' => $this->router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($name, ['_locale' => $current]+$route),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->config['languages'][$current], [], null, $current)
				];
			}

			//Add shorter locale
			if (empty($parameters['alternates'][$shortCurrent = substr($current, 0, 2)])) {
				//Set locale locales context
				$parameters['alternates'][$shortCurrent] = [
					'absolute' => $this->router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $this->router->generate($name, ['_locale' => $current]+$route),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->config['languages'][$current], [], null, $current)
				];
			}
		}

		//Create application form for role_guest
		if ($this->isGranted('ROLE_GUEST')) {
			//Without application form
			if (empty($parameters['forms']['application'])) {
				//Fetch doctrine
				$doctrine = $this->get('doctrine');

				//Get favorites dances
				$danceFavorites = $doctrine->getRepository(Dance::class)->findByUserId($this->getUser()->getId());

				//Set dance default
				$danceDefault = !empty($danceFavorites)?current($danceFavorites):null;

				//Get favorites locations
				$locationFavorites = $doctrine->getRepository(Location::class)->findByUserId($this->getUser()->getId());

				//Set location default
				$locationDefault = !empty($locationFavorites)?current($locationFavorites):null;

				//With admin
				if ($this->isGranted('ROLE_ADMIN')) {
					//Get dances
					$dances = $doctrine->getRepository(Dance::class)->findAll();

					//Get locations
					$locations = $doctrine->getRepository(Location::class)->findAll();
				//Without admin
				} else {
					//Restrict to favorite dances
					$dances = $danceFavorites;

					//Reset favorites
					$danceFavorites = [];

					//Restrict to favorite locations
					$locations = $locationFavorites;

					//Reset favorites
					$locationFavorites = [];
				}

				//With session location id
				//XXX: set in session controller
				if (!empty($parameters['session']['location']['id'])) {
					//Iterate on each location
					foreach($locations as $location) {
						//Found location
						if ($location->getId() == $parameters['session']['location']['id']) {
							//Set location as default
							$locationDefault = $location;

							//Stop search
							break;
						}
					}
				}

				//Create ApplicationType form
				$application = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
					//Set the action
					'action' => $this->generateUrl('rapsys_air_application_add'),
					//Set the form attribute
					'attr' => [ 'class' => 'col' ],
					//Set dance choices
					'dance_choices' => $dances,
					//Set dance default
					'dance_default' => $danceDefault,
					//Set dance favorites
					'dance_favorites' => $danceFavorites,
					//Set location choices
					'location_choices' => $locations,
					//Set location default
					'location_default' => $locationDefault,
					//Set location favorites
					'location_favorites' => $locationFavorites,
					//With user
					'user' => $this->isGranted('ROLE_ADMIN'),
					//Set user choices
					'user_choices' => $doctrine->getRepository(User::class)->findAllWithTranslatedGroupAndCivility($this->translator),
					//Set default user to current
					'user_default' => $this->getUser()->getId(),
					//Set to session slot or evening by default
					//XXX: default to Evening (3)
					'slot_default' => $doctrine->getRepository(Slot::class)->findOneById($parameters['session']['slot']['id']??3)
				]);

				//Add form to context
				$parameters['forms']['application'] = $application->createView();
			}
		//Create login form for anonymous
		} elseif (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create LoginType form
			$login = $this->createForm('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsys_user_login'),
				//Disable password repeated
				'password_repeated' => false,
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$parameters['forms']['login'] = $login->createView();

			//Set field
			$field = [
				//With mail
				'mail' => true,
				//Without civility
				'civility' => false,
				//Without pseudonym
				'pseudonym' => false,
				//Without forename
				'forename' => false,
				//Without surname
				'surname' => false,
				//Without password
				'password' => false,
				//Without slug
				'slug' => false,
				//Without phone
				'phone' => false
			];

			//Get slugger
			$slugger = $this->container->get('rapsys_pack.slugger_util');

			//Create RegisterType form
			$register = $this->createForm('Rapsys\AirBundle\Form\RegisterType', null, $field+[
				//Set the action
				'action' => $this->generateUrl(
					'rapsys_user_register',
					[
						'mail' => $smail = $slugger->short(''),
						'field' => $sfield = $slugger->serialize($field),
						'hash' => $slugger->hash($smail.$sfield)
					]
				),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$parameters['forms']['register'] = $register->createView();
		}

		//With page infos and without facebook texts
		if (empty($parameters['facebook']['texts']) && !empty($parameters['site']['title']) && !empty($parameters['title']) && !empty($parameters['canonical'])) {
			//Set facebook image
			$parameters['facebook']['texts'] = [
				$parameters['site']['title'] => [
					'font' => 'irishgrover',
					'size' => 110
				],
				$parameters['title'] => [
					'align' => 'left'
				],
				$parameters['canonical'] => [
					'align' => 'right',
					'canonical' => true,
					'font' => 'labelleaurore',
					'size' => 50
				]
			];
		}

		//With canonical
		if (!empty($parameters['canonical'])) {
			//Set facebook url
			$parameters['facebook']['metas']['og:url'] = $parameters['canonical'];
		}

		//With empty facebook title and title
		if (empty($parameters['facebook']['metas']['og:title']) && !empty($parameters['title'])) {
			//Set facebook title
			$parameters['facebook']['metas']['og:title'] = $parameters['title'];
		}

		//With empty facebook description and description
		if (empty($parameters['facebook']['metas']['og:description']) && !empty($parameters['description'])) {
			//Set facebook description
			$parameters['facebook']['metas']['og:description'] = $parameters['description'];
		}

		//With locale
		if (!empty($locale)) {
			//Set facebook locale
			$parameters['facebook']['metas']['og:locale'] = $locale;

			//With alternates
			//XXX: locale change when fb_locale=xx_xx is provided is done in FacebookSubscriber
			//XXX: see https://stackoverflow.com/questions/20827882/in-open-graph-markup-whats-the-use-of-oglocalealternate-without-the-locati
			if (!empty($parameters['alternates'])) {
				//Iterate on alternates
				foreach($parameters['alternates'] as $lang => $alternate) {
					if (strlen($lang) == 5) {
						//Set facebook locale alternate
						$parameters['facebook']['metas']['og:locale:alternate'] = str_replace('-', '_', $lang);
					}
				}
			}
		}

		//Without facebook image defined and texts
		if (empty($parameters['facebook']['metas']['og:image']) && !empty($parameters['facebook']['texts'])) {
			//Get facebook image
			$parameters['facebook']['metas'] += $this->getFacebookImage($pathInfo, $parameters['facebook']);
		}

		//Call parent method
		return $this->baseRender($view, $parameters, $response);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see vendor/symfony/framework-bundle/Controller/AbstractController.php
	 */
	public static function getSubscribedServices(): array {
		//Return subscribed services
		return [
			//'logger' => LoggerInterface::class,
			'doctrine' => ManagerRegistry::class,
			'rapsys_pack.path_package' => PackageInterface::class,
			'request_stack' => RequestStack::class,
			'router' => RouterInterface::class,
			'translator' => TranslatorInterface::class
		];
	}
}
