<?php

namespace Rapsys\AirBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface {
	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder() {
		$treeBuilder = new TreeBuilder('rapsys_air');

		// Here you should define the parameters that are allowed to
		// configure your bundle. See the documentation linked above for
		// more information on that topic.
		//Set defaults
		$defaults = [
			'site' => [
				'logo' => '@RapsysAir/../public/png/logo.png',
				'title' => 'Air Libre'
			],
			'copy' => [
				'long' => 'John Doe all rights reserved',
				'short' => 'Copyright 2019'
			],
			'contact' => [
				'name' => 'John Doe',
				'mail' => 'contact@example.com'
			]
		];

		//Here we define the parameters that are allowed to configure the bundle.
		//TODO: see https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/DependencyInjection/Configuration.php for default value and description
		//TODO: see http://symfony.com/doc/current/components/config/definition.html
		//XXX: use bin/console config:dump-reference to dump class infos

		//Here we define the parameters that are allowed to configure the bundle.
		$treeBuilder
			//Parameters
			->getRootNode()
				->addDefaultsIfNotSet()
				->children()
					->arrayNode('site')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('logo')->cannotBeEmpty()->defaultValue($defaults['site']['logo'])->end()
							->scalarNode('title')->cannotBeEmpty()->defaultValue($defaults['site']['title'])->end()
						->end()
					->end()
					->arrayNode('copy')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('long')->defaultValue($defaults['copy']['long'])->end()
							->scalarNode('short')->defaultValue($defaults['copy']['short'])->end()
						->end()
					->end()
					->arrayNode('contact')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('name')->cannotBeEmpty()->defaultValue($defaults['contact']['name'])->end()
							->scalarNode('mail')->cannotBeEmpty()->defaultValue($defaults['contact']['mail'])->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
