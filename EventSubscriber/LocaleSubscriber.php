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

				//Send vary header as current page locale depend on it
				#header('Vary: accept-language');

				//Set locale
				#$request->setLocale($preferred);

				//Set default locale
				#$request->setDefaultLocale($preferred);

				//Get router context
				#$context = $this->router->getContext();

				//Set context locale
				#$context->setParameter('_locale', $preferred);

				//Set back router context
				#$this->router->setContext($context);

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
