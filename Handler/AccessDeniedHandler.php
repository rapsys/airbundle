<?php

namespace Rapsys\AirBundle\Handler;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;

use Rapsys\AirBundle\RapsysAirBundle;

class AccessDeniedHandler implements AccessDeniedHandlerInterface {
	use ControllerTrait;

	//Config array
	protected $config;

	//Context array
	protected $context;

	//Environment instance
	protected $environment;

	//Translator instance
	protected $translator;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(ContainerInterface $container, Environment $environment, RouterInterface $router, RequestStack $stack, TranslatorInterface $translator) {
		//Retrieve config
		$this->config = $container->getParameter(RapsysAirBundle::getAlias());

		//Set the container
		$this->container = $container;

		//Set the translator
		$this->translator = $translator;

		//Set the environment
		$this->environment = $environment;

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
			'facebook' => [
				'heads' => [
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

		//Get current request
		$request = $stack->getCurrentRequest();

		//Get current locale
		$locale = $request->getLocale();

		//Set locale
		$this->context['locale'] = str_replace('_', '-', $locale);

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
				$this->context['canonical'] = $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL);
			} else {
				//Set locale locales context
				$this->context['alternates'][str_replace('_', '-', $current)] = [
					'absolute' => $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $current]+$route),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->config['languages'][$current], [], null, $current)
				];
			}

			//Add shorter locale
			if (empty($this->context['alternates'][$shortCurrent = substr($current, 0, 2)])) {
				//Set locale locales context
				$this->context['alternates'][$shortCurrent] = [
					'absolute' => $router->generate($name, ['_locale' => $current]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $current]+$route),
					'title' => implode('/', $titles),
					'translated' => $this->translator->trans($this->config['languages'][$current], [], null, $current)
				];
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(Request $request, AccessDeniedException $exception) {
		//Set title
		$this->context['page']['title'] = $this->translator->trans('Access denied');

		//Set message
		//XXX: we assume that it's already translated
		$this->context['message'] = $exception->getMessage();

		//With admin
		if ($this->isGranted('ROLE_ADMIN')) {
			//Add trace for admin
			$this->context['trace'] = $exception->getTraceAsString();
		}

		//Render template
		return new Response(
			$this->environment->render(
				'@RapsysAir/security/denied.html.twig',
				$this->context
			),
			403
		);
	}
}
