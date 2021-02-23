<?php

namespace Rapsys\AirBundle\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface {
	/**
	 * {@inheritdoc}
	 */
	protected $router;

	/**
	 * {@inheritdoc}
	 */
	public function __construct(RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * {@inheritdoc}
	 */
	public function onLogoutSuccess(Request $request) {
		//Retrieve logout route
		$logout = $request->get('_route');

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
				//Retrieve route matching path
				$route = $this->router->match($path);

				//Verify that it differ from current one
				if (($name = $route['_route']) == $logout) {
					throw new ResourceNotFoundException('Identical referer and logout route');
				}

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller'], $route['_canonical_route']);

				//Generate url
				$url = $this->router->generate($name, $route);
			//No route matched
			} catch(ResourceNotFoundException $e) {
				//Unset referer to fallback to default route
				unset($referer);
			}
		}

		//Referer empty or unusable
		if (empty($referer)) {
			//Try with / path
			try {
				//Retrieve route matching /
				$route = $this->router->match('/');

				//Verify that it differ from current one
				if (($name = $route['_route']) == $logout) {
					throw new ResourceNotFoundException('Identical referer and logout route');
				}

				//Remove route and controller from route defaults
				unset($route['_route'], $route['_controller'], $route['_canonical_route']);

				//Generate url
				$url = $this->router->generate($name, $route);
			//Get first route from route collection if / path was not matched
			} catch(ResourceNotFoundException $e) {
				//Fetch all routes
				//XXX: this method regenerate the Routing cache making apps very slow
				//XXX: see https://github.com/symfony/symfony-docs/issues/6710
				//XXX: it should be fine to call it without referer and a / route
				foreach($this->router->getRouteCollection() as $name => $route) {
					//Return on first public route excluding logout one
					if (!empty($name) && $name[0] != '_' && $name != $logout) {
						break;
					}
				}

				//Bail out if no route found
				if (!isset($name) || !isset($route)) {
					throw new \RuntimeException('Unable to retrieve default route');
				}

				//Retrieve route defaults
				$defaults = $route->getDefaults();

				//Remove route and controller from route defaults
				unset($defaults['_route'], $defaults['_controller'], $defaults['_canonical_route']);

				//Generate url
				$url = $this->router->generate($name, $defaults);
			}
		}

		//Return redirect response
		return new RedirectResponse($url, 302);
	}
}
