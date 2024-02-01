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
        $treeBuilder = new TreeBuilder('parameters');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
		//Set defaults
		$defaults = [
			'logo' => 'bundles/rapsysair/png/logo.png',
			'title' => 'Open Air',
			'contact_name' => 'Raphaël Gertz',
			'contact_mail' => 'airlibre@rapsys.eu',
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
					->arrayNode('rapsys_air')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('logo')->defaultValue($defaults['logo'])->treatNullLike($defaults['logo'])->isRequired()->end()
							->scalarNode('title')->defaultValue($defaults['title'])->treatNullLike($defaults['title'])->isRequired()->end()
							->scalarNode('contact_name')->defaultValue($defaults['contact_name'])->treatNullLike($defaults['contact_name'])->isRequired()->end()
							->scalarNode('contact_mail')->defaultValue($defaults['contact_mail'])->treatNullLike($defaults['contact_mail'])->isRequired()->end()
						->end()
					->end()
				->end()
			->end();

        return $treeBuilder;
    }
}
