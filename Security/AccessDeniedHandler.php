<?php

namespace Rapsys\AirBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface {
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
	#public function __construct(ContainerInterface $container, Environment $environment, RouterInterface $router, TranslatorInterface $translator, string $alias = 'rapsys_air') {
	public function __construct(ContainerInterface $container, Environment $environment, RouterInterface $router, RequestStack $requestStack, TranslatorInterface $translator, string $alias = 'rapsys_air') {
		//Retrieve config
		$this->config = $container->getParameter($alias);

		//Set the translator
		$this->translator = $translator;

		//Set the environment
		$this->environment = $environment;

		//Set the context
		$this->context = [
			'copy' => [
				'by' => $translator->trans($this->config['copy']['by']),
				'link' => $this->config['copy']['link'],
				'long' => $translator->trans($this->config['copy']['long']),
				'short' => $translator->trans($this->config['copy']['short']),
				'title' => $this->config['copy']['title']
			],
			'site' => [
				'ico' => $this->config['site']['ico'],
				'logo' => $this->config['site']['logo'],
				'png' => $this->config['site']['png'],
				'svg' => $this->config['site']['svg'],
				'title' => $translator->trans($this->config['site']['title']),
				'url' => $router->generate($this->config['site']['url']),
			],
			'canonical' => null,
			'alternates' => []
		];

		//Get current locale
		#$currentLocale = $router->getContext()->getParameters()['_locale'];
		$currentLocale = $requestStack->getCurrentRequest()->getLocale();

		//Set translator locale
		//XXX: allow LocaleSubscriber on the fly locale change for first page
		$this->translator->setLocale($currentLocale);

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
			if ($locale == $currentLocale) {
				//Set locale locales context
				$this->context['canonical'] = $router->generate($name, ['_locale' => $locale]+$route, UrlGeneratorInterface::ABSOLUTE_URL);
			} else {
				//Set locale locales context
				$this->context['alternates'][] = [
					'lang' => $locale,
					'absolute' => $router->generate($name, ['_locale' => $locale]+$route, UrlGeneratorInterface::ABSOLUTE_URL),
					'relative' => $router->generate($name, ['_locale' => $locale]+$route),
					'title' => implode('/', $titles),
					'translated' => $translator->trans($this->config['languages'][$locale], [], null, $locale)
				];
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function handle(Request $request, AccessDeniedException $exception) {
		//Set section
		$section = $this->translator->trans('Access denied');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Set message
		//XXX: we assume that it's already translated
		$message = $exception->getMessage();

		//Render template
		return new Response(
			$this->environment->render(
				'@RapsysAir/security/denied.html.twig',
				[
					'title' => $title,
					'section' => $section,
					'message' => $message
				]+$this->context
			),
			403
		);
	}
}
