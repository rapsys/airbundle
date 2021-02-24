<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\AirBundle\Entity\User;

class SnippetController extends DefaultController {
	/**
	 * Add snippet
	 *
	 * @desc Persist snippet in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function add(Request $request) {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_GUEST', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));

		//Create SnippetType form
		$form = $this->container->get('form.factory')->createNamed(
			//Set name
			'snipped_'.$request->getLocale().'_'.$request->get('location'),
			//Set type
			'Rapsys\AirBundle\Form\SnippetType',
			//Set data
			null,
			//Set options
			[
				//Set the action
				'action' => $this->generateUrl('rapsys_air_snippet_add', ['location' => $request->get('location')]),
				//Set the form attribute
				'attr' => []
			]
		);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Prevent creating snippet for other user unless admin
		if ($form->get('user')->getData() !== $this->getUser()) {
			//Prevent non-admin to access here
			$this->denyAccessUnlessGranted('ROLE_ADMIN', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));
		}

		//Handle invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Snippet add');

			//Set title
			$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

			//Render the view
			return $this->render('@RapsysAir/snippet/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get manager
		$manager = $doctrine->getManager();

		//Get snippet
		$snippet = $form->getData();

		//Set created
		$snippet->setCreated(new \DateTime('now'));

		//Set updated
		$snippet->setUpdated(new \DateTime('now'));

		//Queue snippet save
		$manager->persist($snippet);

		//Flush to get the ids
		$manager->flush();

		//Add notice
		$this->addFlash('notice', $this->translator->trans('Snippet in %locale% %location% for %user% created', ['%locale%' => $snippet->getLocale(), '%location%' => $this->translator->trans('at '.$snippet->getLocation()), '%user%' => $snippet->getUser()]));

		//Extract and process referer
		if ($referer = $request->headers->get('referer')) {
			//Create referer request instance
			$req = Request::create($referer);

			//Get referer path
			$path = $req->getPathInfo();

			//Get referer query string
			$query = $req->getQueryString();

			//Remove script name
			$path = str_replace($request->getScriptName(), '', $path);

			//Try with referer path
			try {
				//Save old context
				$oldContext = $this->router->getContext();

				//Force clean context
				//XXX: prevent MethodNotAllowedException because current context method is POST in onevendor/symfony/routing/Matcher/Dumper/CompiledUrlMatcherTrait.php+42
				$this->router->setContext(new RequestContext());

				//Retrieve route matching path
				$route = $this->router->match($path);

				//Reset context
				$this->router->setContext($oldContext);

				//Clear old context
				unset($oldContext);

				//Extract name
				$name = $route['_route'];

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller']);

				//Check if snippet view route
				if ($name == 'rapsys_air_user_view' && !empty($route['id'])) {
					//Replace id
					$route['id'] = $snippet->getUser()->getId();
				//Other routes
				} else {
					//Set snippet
					$route['snippet'] = $snippet->getId();
				}

				//Generate url
				return $this->redirectToRoute($name, $route);
			//No route matched
			} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air', ['snippet' => $snippet->getId()]);
	}

	/**
	 * Edit snippet
	 *
	 * @desc Persist snippet in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function edit(Request $request, $id) {
		//Prevent non-guest to access here
		$this->denyAccessUnlessGranted('ROLE_GUEST', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get snippet
		if (empty($snippet = $doctrine->getRepository(Snippet::class)->findOneById($id))) {
			throw $this->createNotFoundException($this->translator->trans('Unable to find snippet: %id%', ['%id%' => $id]));
		}

		//Create SnippetType form
		$form = $this->container->get('form.factory')->createNamed(
			//Set name
			'snipped_'.$request->getLocale().'_'.$snippet->getLocation()->getId(),
			//Set type
			'Rapsys\AirBundle\Form\SnippetType',
			//Set data
			$snippet,
			//Set options
			[
				//Set the action
				'action' => $this->generateUrl('rapsys_air_snippet_edit', ['id' => $id]),
				//Set the form attribute
				'attr' => []
			]
		);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Prevent creating snippet for other user unless admin
		if ($form->get('user')->getData() !== $this->getUser()) {
			//Prevent non-admin to access here
			$this->denyAccessUnlessGranted('ROLE_ADMIN', null, $this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));
		}

		//Handle invalid form
		if (!$form->isSubmitted() || !$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Snippet %id%', ['%id%' => $id]);

			//Set title
			$title = $this->translator->trans($this->config['site']['title']).' - '.$section;

			//Render the view
			return $this->render('@RapsysAir/snippet/edit.html.twig', ['id' => $id, 'title' => $title, 'section' => $section, 'form' => $form->createView()]+$this->context);
		}

		//Get manager
		$manager = $doctrine->getManager();

		//Set updated
		$snippet->setUpdated(new \DateTime('now'));

		//Queue snippet save
		$manager->persist($snippet);

		//Flush to get the ids
		$manager->flush();

		//Add notice
		$this->addFlash('notice', $this->translator->trans('Snippet %id% in %locale% %location% for %user% updated', ['%id%' => $id, '%locale%' => $snippet->getLocale(), '%location%' => $this->translator->trans('at '.$snippet->getLocation()), '%user%' => $snippet->getUser()]));

		//Extract and process referer
		if ($referer = $request->headers->get('referer')) {
			//Create referer request instance
			$req = Request::create($referer);

			//Get referer path
			$path = $req->getPathInfo();

			//Get referer query string
			$query = $req->getQueryString();

			//Remove script name
			$path = str_replace($request->getScriptName(), '', $path);

			//Try with referer path
			try {
				//Save old context
				$oldContext = $this->router->getContext();

				//Force clean context
				//XXX: prevent MethodNotAllowedException because current context method is POST in onevendor/symfony/routing/Matcher/Dumper/CompiledUrlMatcherTrait.php+42
				$this->router->setContext(new RequestContext());

				//Retrieve route matching path
				$route = $this->router->match($path);

				//Reset context
				$this->router->setContext($oldContext);

				//Clear old context
				unset($oldContext);

				//Extract name
				$name = $route['_route'];

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller']);

				//Check if snippet view route
				if ($name == 'rapsys_air_user_view' && !empty($route['id'])) {
					//Replace id
					$route['id'] = $snippet->getUser()->getId();
				//Other routes
				} else {
					//Set snippet
					$route['snippet'] = $snippet->getId();
				}

				//Generate url
				return $this->redirectToRoute($name, $route);
			//No route matched
			} catch(MethodNotAllowedException|ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air', ['snippet' => $snippet->getId()]);
	}
}
