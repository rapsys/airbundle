<?php

namespace Rapsys\AirBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface {
	//Config array
	protected $config;

	//Translator instance
	protected $translator;

	//Environment instance
	protected $environment;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(ContainerInterface $container, TranslatorInterface $translator, Environment $environment) {
		//Retrieve config
		$this->config = $container->getParameter($this->getAlias());

		//Set the translator
		$this->translator = $translator;

		//Set the environment
		$this->environment = $environment;
	}

	/**
	 * {@inheritdoc}
	 */
#use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
	#public function login(Request $request, AuthenticationUtils $authenticationUtils) {
	public function handle(Request $request, AccessDeniedException $accessDeniedException) {
		//Set section
		$section = $this->translator->trans('Access denied');

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Set message
		$message = $this->translator->trans($accessDeniedException->getMessage());

		//Render template
		return new Response(
			$this->environment->render(
				'@RapsysAir/security/denied.html.twig',
				['title' => $title, 'section' => $section, 'message' => $message]
			),
			403
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return 'rapsys_air';
	}
}
