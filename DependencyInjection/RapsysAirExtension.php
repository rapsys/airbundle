<?php

namespace Rapsys\AirBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Translation\Loader\ArrayLoader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class RapsysAirExtension extends Extension implements PrependExtensionInterface {
	/**
	 * Prepend the configuration
	 *
	 * @desc Preload the configuration to allow sourcing as parameters
	 * {@inheritdoc}
	 */
	public function prepend(ContainerBuilder $container) {
		//Process the configuration
		$configs = $container->getExtensionConfig($this->getAlias());

		//Load configuration
		$configuration = $this->getConfiguration($configs, $container);

		//Process the configuration to get merged config
		$config = $this->processConfiguration($configuration, $configs);

		//Detect when no user configuration is provided
		if ($configs === [[]]) {
			//Prepend default config
			$container->prependExtensionConfig($this->getAlias(), $config);
		}

		//Save configuration in parameters
		$container->setParameter($this->getAlias(), $config);

		//Store flattened array in parameters
		foreach($this->flatten($config, $this->getAlias()) as $k => $v) {
			$container->setParameter($k, $v);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(array $configs, ContainerBuilder $container) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAlias() {
		return 'rapsys_air';
	}

	/**
	 * The function that parses the array to flatten it into a one level depth array
	 *
	 * @param $array	The config values array
	 * @param $path		The current key path
	 * @param $depth	The maxmium depth
	 * @param $sep		The separator string
	 */
	protected function flatten($array, $path = '', $depth = 10, $sep = '.') {
		//Init res
		$res = array();

		//Pass through non hashed or empty array
		if ($depth && is_array($array) && ($array === [] || array_keys($array) === range(0, count($array) - 1))) {
			$res[$path] = $array;
		//Flatten hashed array
		} elseif ($depth && is_array($array)) {
			foreach($array as $k => $v) {
				$sub = $path ? $path.$sep.$k:$k;
				$res += $this->flatten($v, $sub, $depth - 1, $sep);
			}
		//Pass scalar value directly
		} else {
			$res[$path] = $array;
		}

		//Return result
		return $res;
	}
}
