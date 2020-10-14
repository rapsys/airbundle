<?php

namespace Rapsys\AirBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class LocaleSubscriber implements EventSubscriberInterface {
	private $locales;
	private $router;

	public function __construct(RouterInterface $router, array $locales = ['en']) {
		$this->locales = $locales;
		$this->router = $router;
	}

	public function onKernelRequest(RequestEvent $event) {
		//Retrieve request
		$request = $event->getRequest();

		//Check for session
		//XXX: people blocking cookies will be stuck to preferred language version
		if (!$request->hasPreviousSession()) {
			//Get preferred language
			//XXX: default language is unused, it will return locales[0] if everything fail
			$preferred = $request->getPreferredLanguage($this->locales);

			//Check if preferred language differs from current request locale
			if ($preferred != $request->getLocale()) {
				//Save preferred locale in session
				$request->getSession()->set('_locale', $preferred);

				//Generate current route with preferred language
				//XXX: may trigger a Symfony\Component\Routing\Exception\RouteNotFoundException if route is not found for preferred locale
				$uri = $this->router->generate(
					//Current route
					$request->get('_route'),
					//Force preferred locale
					['_locale' => $preferred]+$request->get('_route_params')
				);

				//Regenerate route with preferred locale
				$event->setResponse(new RedirectResponse($uri, 302));

				//End process
				return;
			}
		}
	}

	public static function getSubscribedEvents() {
		return [
			// must be registered before the default locale listener
			KernelEvents::REQUEST => [['onKernelRequest', 10]]
		];
	}
}
