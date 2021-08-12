<?php

namespace Rapsys\AirBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class FacebookSubscriber implements EventSubscriberInterface {
	///Supported locales array
	private $locales;

	///Router instance
	private $router;

	/*
	 * Inject router interface and locales
	 *
	 * @param RouterInterface $router The router instance
	 * @param array $locales The supported locales
	 */
	public function __construct(RouterInterface $router, array $locales) {
		//Set locales
		$this->locales = $locales;

		//Set router
		$this->router = $router;
	}

	/**
	 * Change locale for request with ?fb_locale=xx
	 *
	 * @param RequestEvent The request event
	 */
	public function onKernelRequest(RequestEvent $event) {
		//Retrieve request
		$request = $event->getRequest();

		//Check for facebook locale
		if (
			$request->query->has('fb_locale') &&
			in_array($preferred = $request->query->get('fb_locale'), $this->locales)
		) {
			//Set locale
			$request->setLocale($preferred);

			//Set default locale
			$request->setDefaultLocale($preferred);

			//Get router context
			$context = $this->router->getContext();

			//Set context locale
			$context->setParameter('_locale', $preferred);

			//Set back router context
			$this->router->setContext($context);
		}
	}

	/**
	 * Get subscribed events
	 *
	 * @return array The subscribed events
	 */
	public static function getSubscribedEvents() {
		return [
			// must be registered before the default locale listener
			KernelEvents::REQUEST => [['onKernelRequest', 10]]
		];
	}
}
