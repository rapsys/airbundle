<?php

namespace Rapsys\AirBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RapsysAirExtension extends Extension {
	/**
	 * {@inheritdoc}
	 */
	public function load(array $configs, ContainerBuilder $container) {
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');

		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		//Set default config in parameter
		if (!$container->hasParameter($alias = $this->getAlias())) {
			$container->setParameter($alias, $config[$alias]);
		} else {
			$config[$alias] = $container->getParameter($alias);
		}

		//Transform the one level tree in flat parameters
		foreach($config[$alias] as $k => $v) {
			//Set is as parameters
			$container->setParameter($alias.'.'.$k, $v);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return 'rapsys_air';
	}
}
