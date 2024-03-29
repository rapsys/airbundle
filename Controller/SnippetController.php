<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

use Rapsys\AirBundle\Entity\Location;
use Rapsys\AirBundle\Entity\Snippet;
use Rapsys\AirBundle\Entity\User;

class SnippetController extends DefaultController {
	/**
	 * Add snippet
	 *
	 * Persist snippet in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function add(Request $request) {
		//Without guest role
		if (!$this->checker->isGranted('ROLE_GUEST')) {
			//Throw 403
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));
		}

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
				'action' => $this->generateUrl('rapsysair_snippet_add', ['location' => $request->get('location')]),
				//Set the form attribute
				'attr' => []
			]
		);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Prevent creating snippet for other user unless admin
		if ($form->get('user')->getData() !== $this->getUser()) {
			//Without admin role
			if (!$this->checker->isGranted('ROLE_ADMIN')) {
				//Throw 403
				throw $this->createAccessDeniedException($this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));
			}
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
				if ($name == 'rapsysair_user_view' && !empty($route['id'])) {
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
		return $this->redirectToRoute('rapsysair', ['snippet' => $snippet->getId()]);
	}

	/**
	 * Edit snippet
	 *
	 * Persist snippet in database
	 *
	 * @param Request $request The request instance
	 *
	 * @return Response The rendered view or redirection
	 *
	 * @throws \RuntimeException When user has not at least guest role
	 */
	public function edit(Request $request, $id) {
		//Without guest role
		if (!$this->checker->isGranted('ROLE_GUEST')) {
			//Throw 403
			throw $this->createAccessDeniedException($this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Guest')]));
		}

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
				'action' => $this->generateUrl('rapsysair_snippet_edit', ['id' => $id]),
				//Set the form attribute
				'attr' => []
			]
		);

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Prevent creating snippet for other user unless admin
		if ($form->get('user')->getData() !== $this->getUser()) {
			//Without admin role
			if (!$this->checker->isGranted('ROLE_ADMIN')) {
				//Throw 403
				throw $this->createAccessDeniedException($this->translator->trans('Unable to access this page without role %role%!', ['%role%' => $this->translator->trans('Admin')]));
			}
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

		//With image
		//TODO: add delete button ???
		if ($image = $form->get('image')->getData()) {
			//Get public path
			#$public = $this->container->get('kernel')->getBundle('RapsysAirBundle')->getPath().'/Resources/public';
			#$public = $this->container->get('kernel')->locateResource('@RapsysAirBundle/Resources/public');
			$public = $this->getPublicPath();

			//Create imagick object
			$imagick = new \Imagick();

			//Read image
			$imagick->readImage($image->getRealPath());

			//Set destination
			//XXX: uploaded path location/<userId>/<locationId>.png and session image location/<userId>/<locationId>/<sessionId>.jpeg
			//XXX: default path location/default.png and session location/default/<sessionId>.jpeg
			$destination = $public.'/location/'.$snippet->getUser()->getId().'/'.$snippet->getLocation()->getId().'.png';

			//Check target directory
			if (!is_dir($dir = dirname($destination))) {
				//Create filesystem object
				$filesystem = new Filesystem();

				try {
					//Create dir
					//XXX: set as 0775, symfony umask (0022) will reduce rights (0755)
					$filesystem->mkdir($dir, 0775);
				} catch (IOExceptionInterface $e) {
					//Throw error
					throw new \Exception(sprintf('Output directory "%s" do not exists and unable to create it', $dir), 0, $e);
				}
			}

			//Save image
			if (!$imagick->writeImage($destination)) {
				//Throw error
				throw new \Exception(sprintf('Unable to write image "%s"', $destination));
			}
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
				if ($name == 'rapsysair_user_view' && !empty($route['id'])) {
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
		return $this->redirectToRoute('rapsysair', ['snippet' => $snippet->getId()]);
	}
}
