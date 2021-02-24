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
	public function show(Request $request, FlattenException $exception) {
		//Set section
		$section = $exception->getStatusCode().' '.$this->translator->trans($exception->getStatusText());

		//Set title
		$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

		//Set the message
		$message = $exception->getMessage();

		//Init trace
		$trace = null;

		//Prevent non admin access to trace
		if ($this->isGranted('ROLE_ADMIN')) {
			//Set project dir
			$projectDir = $this->container->getParameter('kernel.project_dir').'/';

			//Set the trace
			//$trace = $exception->getAsString();
			$trace = '';

			//Iterate on array
			foreach($exception->toArray() as $current) {
				$trace .= $current['class'];

				if (!empty($current['message'])) {
					$trace .= ': '.$current['message'];
				}

				if (!empty($current['trace'])) {
					foreach($current['trace'] as $id => $sub) {
						$trace .= "\n".'#'.$id.' '.$sub['class'].$sub['type'].$sub['function'];
						if (!empty($sub['args'])) {
							$trace .= '('.implode(', ', array_map(function($v){return $v[0].' '.$v[1];}, $sub['args'])).')';
						}
						$trace .= ' in '.str_replace($projectDir, '', $sub['file']).':'.$sub['line'];
					}
				}
			}
		}

		//Render template
		return $this->render(
			'@RapsysAir/error.html.twig',
			['title' => $title, 'section' => $section, 'message' => $message, 'trace' => $trace]+$this->context
		);
	}
}
