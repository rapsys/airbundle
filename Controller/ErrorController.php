<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
#use Symfony\Component\Security\Core\Exception\AccessDeniedException;
#use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
#use Symfony\Component\DependencyInjection\ContainerInterface;
#use Symfony\Component\Routing\RouterInterface;
#use Symfony\Component\Translation\TranslatorInterface;
#use Twig\Environment;

#use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\ErrorHandler\Exception\FlattenException;

class ErrorController extends DefaultController {
	/**
	 * {@inheritdoc}
	 */
	public function preview(Request $request, FlattenException $exception) {
		//Set section
		$section = $exception->getStatusCode().' '.$this->translator->trans($exception->getStatusText());

		//Set title
		$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

		//Set the message
		$message = $exception->getMessage();

		//Set the trace
		$trace = $exception->getAsString();

		//Render template
		return $this->render(
			'@RapsysAir/error.html.twig',
			['title' => $title, 'section' => $section, 'message' => $message, 'trace' => $trace]+$this->context
		);
	}
}
