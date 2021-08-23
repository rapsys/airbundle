<?php

namespace Rapsys\AirBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

use Rapsys\AirBundle\RapsysAirBundle;
use Rapsys\AirBundle\Controller\AbstractController;

/**
 * {@inheritdoc}
 */
class AccessDeniedHandler extends AbstractController implements AccessDeniedHandlerInterface {
	/**
	 * {@inheritdoc}
	 */
	public function handle(Request $request, AccessDeniedException $exception) {
		//Set title
		$this->context['title'] = $this->translator->trans('Access denied');

		//Set message
		//XXX: we assume that it's already translated
		$this->context['message'] = $exception->getMessage();

		//With admin
		if ($this->isGranted('ROLE_ADMIN')) {
			//Add trace for admin
			$this->context['trace'] = $exception->getTraceAsString();
		}

		//Render template
		$response = $this->render('@RapsysAir/security/denied.html.twig', $this->context);
		$response->setStatusCode(403);
		$response->setEtag(md5($response->getContent()));
		$response->setPublic();
		$response->isNotModified($request);

		//Return response
		return $response;
	}
}
