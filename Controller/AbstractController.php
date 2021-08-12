<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Rapsys\AirBundle\Entity\Slot;

use Rapsys\UserBundle\Utils\Slugger;

/**
 *
 * Provides common features needed in controllers.
 *
 * @todo: pass request_stack, router, slugger_util, path_package as constructor argument ?
 *
 * {@inheritdoc}
 */
abstract class AbstractController extends BaseAbstractController {
	use ControllerTrait {
		//Rename render as baseRender
		render as protected baseRender;
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

		//Get router
		$router = $this->container->get('router');

		//Get context path
		$pathInfo = $router->getContext()->getPathInfo();

		//Iterate on locales excluding current one
		foreach($this->config['locales'] as $current) {
			//Set titles
			$titles = [];

			//Iterate on other locales
			foreach(array_diff($this->config['locales'], [$current]) as $other) {
				$titles[$other] = $this->translator->trans($this->config['languages'][$current], [], null, $other);
			}

			//Retrieve route matching path
			$route = $router->match($pathInfo);

			//Get route name
			$name = $route['_route'];

			//Unset route name
			unset($route['_route']);

			//With current locale
			if ($current == $locale) {
				//Set locale locales context
				$parameters['canonical'] = $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL);
			} else {
				//Set locale locales context
				$parameters['alternates'][str_replace('_', '-', $current)] = [
					'absolute' => $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $current]+$route),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->config['languages'][$current], [], null, $current)
				];
			}

			//Add shorter locale
			if (empty($parameters['alternates'][$shortCurrent = substr($current, 0, 2)])) {
				//Set locale locales context
				$parameters['alternates'][$shortCurrent] = [
					'absolute' => $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $current]+$route),
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
		if (empty($parameters['facebook']['texts']) && !empty($parameters['site']['title']) && !empty($parameters['page']['title']) && !empty($parameters['canonical'])) {
			//Set facebook image
			$parameters['facebook']['texts'] = [
				$parameters['site']['title'] => [
					'font' => 'irishgrover',
					'size' => 110
				],
				$parameters['page']['title'] => [
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

		//With page title
		if (!empty($parameters['page']['title'])) {
			//Set facebook title
			$parameters['facebook']['metas']['og:title'] = $parameters['page']['title'];
		}

		//With page description
		if (!empty($parameters['page']['description'])) {
			//Set facebook description
			$parameters['facebook']['metas']['og:description'] = $parameters['page']['description'];
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
	 * TODO: define this function to limit subscribed services ???
	 * XXX: see vendor/symfony/framework-bundle/Controller/AbstractController.php
	 *
	public static function getSubscribedServices() {
		//TODO: add asset.package ?
        return [
            'router' => '?'.RouterInterface::class,
            'request_stack' => '?'.RequestStack::class,
            'http_kernel' => '?'.HttpKernelInterface::class,
            'serializer' => '?'.SerializerInterface::class,
            'session' => '?'.SessionInterface::class,
            'security.authorization_checker' => '?'.AuthorizationCheckerInterface::class,
            'templating' => '?'.EngineInterface::class,
            'twig' => '?'.Environment::class,
            'doctrine' => '?'.ManagerRegistry::class,
            'form.factory' => '?'.FormFactoryInterface::class,
            'security.token_storage' => '?'.TokenStorageInterface::class,
            'security.csrf.token_manager' => '?'.CsrfTokenManagerInterface::class,
            'parameter_bag' => '?'.ContainerBagInterface::class,
            'message_bus' => '?'.MessageBusInterface::class,
            'messenger.default_bus' => '?'.MessageBusInterface::class,
        ];
	}*/
}
