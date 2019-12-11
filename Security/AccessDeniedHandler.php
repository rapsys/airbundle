<?php

namespace Rapsys\AirBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
	public function __construct(ContainerInterface $container, Environment $environment, RouterInterface $router, TranslatorInterface $translator, string $alias = 'rapsys_air') {
		//Retrieve config
		$this->config = $container->getParameter($alias);

		//Set the translator
		$this->translator = $translator;

		//Set the environment
		$this->environment = $environment;

		//Set the context
		$this->context = [
			'copy_long' => $translator->trans($this->config['copy']['long']),
			'copy_short' => $translator->trans($this->config['copy']['short']),
			'site_ico' => $this->config['site']['ico'],
			'site_logo' => $this->config['site']['logo'],
			'site_png' => $this->config['site']['png'],
			'site_svg' => $this->config['site']['svg'],
			'site_title' => $translator->trans($this->config['site']['title']),
			'site_url' => $router->generate($this->config['site']['url'])
		];
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
