<?php declare(strict_types=1);

/*
 * This file is part of the Rapsys AirBundle package.
 *
 * (c) Raphaël Gertz <symfony@rapsys.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rapsys\AirBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
	public function handle(Request $request, AccessDeniedException $exception): Response {
		//Set title
		$this->context['title'] = $this->translator->trans('Access denied');

		//Set message
		//XXX: we assume that it's already translated
		$this->context['message'] = $exception->getMessage();

		//With admin
		if ($this->checker->isGranted('ROLE_ADMIN')) {
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
