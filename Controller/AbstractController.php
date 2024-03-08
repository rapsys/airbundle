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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseAbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Twig\Environment;

use Rapsys\AirBundle\Entity\Dance;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\RapsysAirBundle;

use Rapsys\PackBundle\Util\FacebookUtil;
use Rapsys\PackBundle\Util\ImageUtil;
use Rapsys\PackBundle\Util\MapUtil;
use Rapsys\PackBundle\Util\SluggerUtil;

/**
 * Provides common features needed in controllers.
 *
 * {@inheritdoc}
 */
abstract class AbstractController extends BaseAbstractController implements ServiceSubscriberInterface {
	/**
	 * Config array
	 */
	protected array $config;

	/**
	 * Context array
	 */
	protected array $context;

	/**
	 * Locale string
	 */
	protected string $locale;

	/**
	 * Modified DateTime
	 */
	protected \DateTime $modified;

	/**
	 * DatePeriod instance
	 */
	protected \DatePeriod $period;

	/**
	 * Request instance
	 */
	protected Request $request;

	/**
	 * Route string
	 */
	protected string $route;

	/**
	 * Route params array
	 */
	protected array $routeParams;

	/**
	 * Abstract constructor
	 *
	 * @param AuthorizationCheckerInterface $checker The container instance
	 * @param ContainerInterface $container The container instance
	 * @param AccessDecisionManagerInterface $decision The decision instance
	 * @param ManagerRegistry $doctrine The doctrine instance
	 * @param FacebookUtil $facebook The facebook instance
	 * @param FormFactoryInterface $factory The factory instance
	 * @param ImageUtil $image The image instance
	 * @param MailerInterface $mailer The mailer instance
	 * @param EntityManagerInterface $manager The manager instance
	 * @param MapUtil $map The map instance
	 * @param PackageInterface $package The package instance
	 * @param RouterInterface $router The router instance
	 * @param Security $security The security instance
	 * @param SluggerUtil $slugger The slugger instance
	 * @param RequestStack $stack The stack instance
	 * @param TranslatorInterface $translator The translator instance
	 * @param Environment $twig The twig environment instance
	 *
	 * @TODO move all that stuff to setSlugger('@slugger') setters with a calls: [ setSlugger: [ '@slugger' ] ] to unbload classes ???
	 * @TODO add a calls: [ ..., prepare: ['@???'] ] that do all the logic that can't be done in constructor because various things are not available
	 */
	public function __construct(protected AuthorizationCheckerInterface $checker, protected ContainerInterface $container, protected AccessDecisionManagerInterface $decision, protected ManagerRegistry $doctrine, protected FacebookUtil $facebook, protected FormFactoryInterface $factory, protected ImageUtil $image, protected MailerInterface $mailer, protected EntityManagerInterface $manager, protected MapUtil $map, protected PackageInterface $package, protected RouterInterface $router, protected Security $security, protected SluggerUtil $slugger, protected RequestStack $stack, protected TranslatorInterface $translator, protected Environment $twig, protected int $limit = 5) {
		//Retrieve config
		$this->config = $container->getParameter(RapsysAirBundle::getAlias());

		//Set period
		$this->period = new \DatePeriod(
			//Start from first monday of week
			new \DateTime('Monday this week'),
			//Iterate on each day
			new \DateInterval('P1D'),
			//End with next sunday and 4 weeks
			//XXX: we can't use isGranted here as AuthenticatedVoter deny access because user is likely not authenticated yet :'(
			new \DateTime('Monday this week + 2 week')
		);

		//Get main request
		$this->request = $this->stack->getMainRequest();

		//Get current locale
		$this->locale = $this->request->getLocale();

		//Set canonical
		$canonical = null;

		//Set alternates
		$alternates = [];

		//Set route
		//TODO: default to not found route ???
		//TODO: pour une url not found, cet attribut n'est pas défini, comment on fait ???
		//XXX: on génère une route bidon par défaut ???
		$this->route = $this->request->attributes->get('_route');

		//Set route params
		$this->routeParams = $this->request->attributes->get('_route_params');

		//With route and routeParams
		if ($this->route !== null && $this->routeParams !== null) {
			//Set canonical
			$canonical = $this->router->generate($this->route, $this->routeParams, UrlGeneratorInterface::ABSOLUTE_URL);

			//Set alternates
			$alternates = [
				substr($this->locale, 0, 2) => [
					'absolute' => $canonical
				]
			];
		}

		//Set the context
		$this->context = [
			'alternates' => $alternates,
			'canonical' => $canonical,
			'contact' => [
				'address' => $this->config['contact']['address'],
				'name' => $this->translator->trans($this->config['contact']['name'])
			],
			'copy' => [
				'by' => $this->translator->trans($this->config['copy']['by']),
				'link' => $this->config['copy']['link'],
				'long' => $this->translator->trans($this->config['copy']['long']),
				'short' => $this->translator->trans($this->config['copy']['short']),
				'title' => $this->config['copy']['title']
			],
			'description' => null,
			'donate' => $this->config['donate'],
			'facebook' => [
				'og:type' => 'article',
				'og:site_name' => $title = $this->translator->trans($this->config['title']),
				'og:url' => $canonical,
				#'fb:admins' => $this->config['facebook']['admins'],
				'fb:app_id' => $this->config['facebook']['apps']
			],
			//XXX: TODO: only generate it when fb robot request the url ???
			'fbimage' => [
				'texts' => [
					$title => [
						'font' => 'irishgrover',
						'size' => 110
					]
				]
			],
			'icon' => $this->config['icon'],
			'keywords' => null,
			'locale' => str_replace('_', '-', $this->locale),
			'logo' => $this->config['logo'],
			'forms' => [],
			'root' => $this->router->generate($this->config['root']),
			'title' => [
				'page' => null,
				'section' => null,
				'site' => $title
			]
		];
	}

	/**
	 * Renders a view
	 *
	 * {@inheritdoc}
	 */
	protected function render(string $view, array $parameters = [], Response $response = null): Response {
		//Create response when null
		$response ??= new Response();

		//Create application form for role_guest
		if ($this->checker->isGranted('ROLE_GUEST')) {
			//Without application form
			if (empty($parameters['forms']['application'])) {
				//Get favorites dances
				$danceFavorites = $this->doctrine->getRepository(Dance::class)->findByUserId($this->security->getUser()->getId());

				//Set dance default
				$danceDefault = !empty($danceFavorites)?current($danceFavorites):null;

				//Get favorites locations
				$locationFavorites = $this->doctrine->getRepository(Location::class)->findByUserId($this->security->getUser()->getId());

				//Set location default
				$locationDefault = !empty($locationFavorites)?current($locationFavorites):null;

				//With admin
				if ($this->checker->isGranted('ROLE_ADMIN')) {
					//Get dances
					$dances = $this->doctrine->getRepository(Dance::class)->findAll();

					//Get locations
					$locations = $this->doctrine->getRepository(Location::class)->findAll();
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

				//With session application dance id
				if (!empty($parameters['session']['application']['dance']['id'])) {
					//Iterate on each dance
					foreach($dances as $dance) {
						//Found dance
						if ($dance->getId() == $parameters['session']['application']['dance']['id']) {
							//Set dance as default
							$danceDefault = $dance;

							//Stop search
							break;
						}
					}
				}

				//With session location id
				//XXX: set in session controller
				//TODO: with new findAll that key by id, it should be as simple as isset($locations[$id]) ?
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
				$application = $this->factory->create('Rapsys\AirBundle\Form\ApplicationType', null, [
					//Set the action
					'action' => $this->generateUrl('rapsysair_application_add'),
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
					'user' => $this->checker->isGranted('ROLE_ADMIN'),
					//Set user choices
					'user_choices' => $this->doctrine->getRepository(User::class)->findChoicesAsArray(),
					//Set default user to current
					'user_default' => $this->security->getUser()->getId(),
					//Set to session slot or evening by default
					//XXX: default to Evening (3)
					'slot_default' => $this->doctrine->getRepository(Slot::class)->findOneById($parameters['session']['slot']['id']??3)
				]);

				//Add form to context
				$parameters['forms']['application'] = $application->createView();
			}
		}/*
		#XXX: removed because it fucks up the seo by displaying register and login form instead of content
		#XXX: until we find a better way, removed !!!
		//Create login form for anonymous
		elseif (!$this->checker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			//Create LoginType form
			$login = $this->factory->create('Rapsys\UserBundle\Form\LoginType', null, [
				//Set the action
				'action' => $this->generateUrl('rapsysuser_login'),
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

			//Create RegisterType form
			$register = $this->factory->create('Rapsys\AirBundle\Form\RegisterType', null, $field+[
				//Set the action
				'action' => $this->generateUrl(
					'rapsysuser_register',
					[
						'mail' => $smail = $this->slugger->short(''),
						'field' => $sfield = $this->slugger->serialize($field),
						'hash' => $this->slugger->hash($smail.$sfield)
					]
				),
				//Set the form attribute
				'attr' => [ 'class' => 'col' ]
			]);

			//Add form to context
			$parameters['forms']['register'] = $register->createView();
		}*/

		//Without alternates
		if (count($parameters['alternates']) <= 1) {
			//Set routeParams
			$routeParams = $this->routeParams;

			//Iterate on locales excluding current one
			foreach($this->config['locales'] as $locale) {
				//With current locale
				if ($locale !== $this->locale) {
					//Set titles
					$titles = [];

					//Set route params locale
					$routeParams['_locale'] = $locale;

					//Iterate on other locales
					foreach(array_diff($this->config['locales'], [$locale]) as $other) {
						//Set other locale title
						$titles[$other] = $this->translator->trans($this->config['languages'][$locale], [], null, $other);
					}

					//Set locale locales context
					$parameters['alternates'][str_replace('_', '-', $locale)] = [
						'absolute' => $this->router->generate($this->route, $routeParams, UrlGeneratorInterface::ABSOLUTE_URL),
						'relative' => $this->router->generate($this->route, $routeParams),
						'title' => implode('/', $titles),
						'translated' => $this->translator->trans($this->config['languages'][$locale], [], null, $locale)
					];

					//Add shorter locale
					if (empty($parameters['alternates'][$shortCurrent = substr($locale, 0, 2)])) {
						//Set locale locales context
						$parameters['alternates'][$shortCurrent] = $parameters['alternates'][str_replace('_', '-', $locale)];
					}
				}
			}
		}

		//With page infos and without facebook texts
		if (count($parameters['fbimage']) <= 1 && isset($parameters['title']) && isset($this->route) && isset($this->routeParams)) {
			//Append facebook image texts
			$parameters['fbimage'] += [
				'texts' => [
					$parameters['title']['page'] => [
						'font' => 'irishgrover',
						'align' => 'left'
					]/*XXX: same problem as url, too long :'(,
					$parameters['description'] => [
						'align' => 'right',
						'canonical' => true,
						'font' => 'labelleaurore',
						'size' => 50
					]*/
				]
			];

			/*With short path info
			We don't add this stupid url in image !!!
			if (strlen($pathInfo = $this->router->generate($this->route, $this->routeParams)) <= 64) {
				 => [
					'align' => 'right',
					'canonical' => true,
					'font' => 'labelleaurore',
					'size' => 50
				 ]
			}*/
		}

		//With empty locations link
		if (empty($parameters['locations_link'])) {
			//Set locations link
			$parameters['locations_link'] = $this->router->generate('rapsysair_location');
		}

		//With empty locations title
		if (empty($parameters['locations_title'])) {
			//Set locations title
			$parameters['locations_title'] = $this->translator->trans('Locations', [], null, $this->locale);
		}

		//With canonical
		if (!empty($parameters['canonical'])) {
			//Set facebook url
			$parameters['facebook']['og:url'] = $parameters['canonical'];
		}

		//With empty facebook title and title
		if (empty($parameters['facebook']['og:title']) && !empty($parameters['title'])) {
			//Set facebook title
			$parameters['facebook']['og:title'] = $parameters['title'];
		}

		//With empty facebook description and description
		if (empty($parameters['facebook']['og:description']) && !empty($parameters['description'])) {
			//Set facebook description
			$parameters['facebook']['og:description'] = $parameters['description'];
		}

		//With locale
		if (!empty($this->locale)) {
			//Set facebook locale
			$parameters['facebook']['og:locale'] = $this->locale;

			//With alternates
			//XXX: locale change when fb_locale=xx_xx is provided is done in FacebookSubscriber
			//XXX: see https://stackoverflow.com/questions/20827882/in-open-graph-markup-whats-the-use-of-oglocalealternate-without-the-locati
			if (!empty($parameters['alternates'])) {
				//Iterate on alternates
				foreach($parameters['alternates'] as $lang => $alternate) {
					if (strlen($lang) == 5) {
						//Set facebook locale alternate
						$parameters['facebook']['og:locale:alternate'] = str_replace('-', '_', $lang);
					}
				}
			}
		}

		//Without facebook image defined and texts
		if (empty($parameters['facebook']['og:image']) && !empty($this->request) && !empty($parameters['fbimage']['texts']) && !empty($this->modified)) {
			//Get facebook image
			$parameters['facebook'] += $this->facebook->getImage($this->request->getPathInfo(), $parameters['fbimage']['texts'], $this->modified->getTimestamp());
		}

		//Call twig render method
		$content = $this->twig->render($view, $parameters);

		//Invalidate OK response on invalid form
		if (200 === $response->getStatusCode()) {
			foreach ($parameters as $v) {
				if ($v instanceof FormInterface && $v->isSubmitted() && !$v->isValid()) {
					$response->setStatusCode(422);
					break;
				}
			}
		}

		//Store content in response
		$response->setContent($content);

		//Return response
		return $response;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @see vendor/symfony/framework-bundle/Controller/AbstractController.php
	 */
	public static function getSubscribedServices(): array {
		//Return subscribed services
		return [
			'doctrine' => ManagerRegistry::class,
			'doctrine.orm.default_entity_manager' => EntityManagerInterface::class,
			'form.factory' => FormFactoryInterface::class,
			'mailer.mailer' => MailerInterface::class,
			'rapsysair.facebook_util' => FacebookUtil::class,
			'rapsyspack.image_util' => ImageUtil::class,
			'rapsyspack.map_util' => MapUtil::class,
			'rapsyspack.path_package' => PackageInterface::class,
			'rapsyspack.slugger_util' => SluggerUtil::class,
			'rapsysuser.access_decision_manager' => AccessDecisionManagerInterface::class,
			'request_stack' => RequestStack::class,
			'router' => RouterInterface::class,
			'security.authorization_checker' => AuthorizationCheckerInterface::class,
			'service_container' => ContainerInterface::class,
			'translator' => TranslatorInterface::class
		];
	}
}
