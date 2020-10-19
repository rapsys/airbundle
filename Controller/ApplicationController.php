<?php

namespace Rapsys\AirBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Form\FormError;
use Rapsys\AirBundle\Entity\Slot;
use Rapsys\AirBundle\Entity\User;
use Rapsys\AirBundle\Entity\Session;
use Rapsys\AirBundle\Entity\Application;

class ApplicationController extends DefaultController {
	/**
	 * Add application
	 *
	 * @desc Persist application and all required dependencies in database
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

		//Create ApplicationType form
		$form = $this->createForm('Rapsys\AirBundle\Form\ApplicationType', null, [
			//Set the action
			'action' => $this->generateUrl('rapsys_air_application_add'),
			//Set the form attribute
			#'attr' => [ 'class' => 'col' ],
			//Set admin
			'admin' => $this->isGranted('ROLE_ADMIN'),
			//Set default user to current
			'user' => $this->getUser()->getId(),
			//Set default slot to evening
			//XXX: default to Evening (3)
			'slot' => $this->getDoctrine()->getRepository(Slot::class)->findOneById(3)
		]);

		//Reject non post requests
		if (!$request->isMethod('POST')) {
			throw new \RuntimeException('Request method MUST be POST');
		}

		//Refill the fields in case of invalid form
		$form->handleRequest($request);

		//Handle invalid form
		if (!$form->isValid()) {
			//Set section
			$section = $this->translator->trans('Application Add');

			//Set title
			$title = $section.' - '.$this->translator->trans($this->config['site']['title']);

			//Render the view
			return $this->render('@RapsysAir/application/add.html.twig', ['title' => $title, 'section' => $section, 'form' => $form]+$this->context);
		}

		//Get doctrine
		$doctrine = $this->getDoctrine();

		//Get manager
		$manager = $doctrine->getManager();

		//Get data
		$data = $form->getData();

		//Protect session fetching
		try {
			//Fetch session
			$session = $doctrine->getRepository(Session::class)->findOneByLocationSlotDate($data['location'], $data['slot'], $data['date']);
		//Catch no session case
		} catch (\Doctrine\ORM\NoResultException $e) {
			//Create the session
			$session = new Session();
			$session->setLocation($data['location']);
			$session->setDate($data['date']);
			$session->setSlot($data['slot']);
			$session->setCreated(new \DateTime('now'));
			$session->setUpdated(new \DateTime('now'));

			//Queue session save
			$manager->persist($session);

			//Flush to get the ids
			#$manager->flush();

			$this->addFlash('notice', $this->translator->trans('Session on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		}

		//Set user
		$user = $this->getUser();

		//Replace with requested user for admin
		if ($this->isGranted('ROLE_ADMIN') && !empty($data['user'])) {
			$user = $this->getDoctrine()->getRepository(User::class)->findOneById($data['user']);
		}

		//Protect application fetching
		try {
			//Retrieve application
			$application = $doctrine->getRepository(Application::class)->findOneBySessionUser($session, $user);

			//Add notice in flash message
			$this->addFlash('warning', $this->translator->trans('Application on %date% %location% %slot% already exists', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		//Catch no application and session without identifier (not persisted&flushed) cases
		} catch (\Doctrine\ORM\NoResultException|\Doctrine\ORM\ORMInvalidArgumentException $e) {
			//Create the application
			$application = new Application();
			$application->setSession($session);
			$application->setUser($user);
			$application->setCreated(new \DateTime('now'));
			$application->setUpdated(new \DateTime('now'));

			//Refresh session updated field
			$session->setUpdated(new \DateTime('now'));

			//Queue session save
			$manager->persist($session);

			//Queue application save
			$manager->persist($application);

			//Flush to get the ids
			$manager->flush();

			//Add notice in flash message
			$this->addFlash('notice', $this->translator->trans('Application on %date% %location% %slot% created', ['%location%' => $this->translator->trans('at '.$data['location']), '%slot%' => $this->translator->trans('the '.strtolower($data['slot'])), '%date%' => $data['date']->format('Y-m-d')]));
		}

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

				//Generate url
				return $this->redirectToRoute($name, ['session' => $session->getId()]+$route);
			//No route matched
			} catch(ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Redirect to cleanup the form
		return $this->redirectToRoute('rapsys_air', ['session' => $session->getId()]);
	}
}
