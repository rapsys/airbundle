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
		//XXX: don't flatten rapsys_air.site.png key which is required to be an array
		foreach($this->flatten($config, $this->getAlias(), 10, '.', ['rapsys_air.site.png']) as $k => $v) {
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
	 * @param $skip		The skipped paths array
	 */
	protected function flatten($array, $path = '', $depth = 10, $sep = '.', $skip = []) {
		//Init res
		$res = array();

		//Detect numerical only array
		//count(array_filter($array, function($k) { return !is_numeric($k); }, ARRAY_FILTER_USE_KEY)) == 0
		//array_reduce(array_keys($array), function($c, $k) { return $c += !is_numeric($k); }, 0)

		//Flatten hashed array until depth reach zero
		if ($depth && is_array($array) && $array !== [] && !in_array($path, $skip)) {
			foreach($array as $k => $v) {
				$sub = $path ? $path.$sep.$k:$k;
				$res += $this->flatten($v, $sub, $depth - 1, $sep, $skip);
			}
		//Pass scalar value directly
		} else {
			$res[$path] = $array;
		}

		//Return result
		return $res;
	}
}
