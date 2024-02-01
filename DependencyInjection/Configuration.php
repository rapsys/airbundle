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
				'ico' => '@RapsysAir/ico/icon.ico',
				'logo' => '@RapsysAir/png/logo.png',
				//The png icon array
				//XXX: see https://www.emergeinteractive.com/insights/detail/the-essentials-of-favicons/
				//XXX: see https://caniuse.com/#feat=link-icon-svg
				'png' => [
					//Default
					256 => '@RapsysAir/png/icon.256.png',

					//For google
					//Chrome for Android home screen icon
					196 => '@RapsysAir/png/icon.196.png',
					//Google Developer Web App Manifest Recommendation
					192 => '@RapsysAir/png/icon.192.png',
					//Chrome Web Store icon
					128 => '@RapsysAir/png/icon.128.png',

					//Fallback
					32 => '@RapsysAir/png/icon.32.png',

					//For apple
					//XXX: old obsolete format: [57, 72, 76, 114, 120, 144]
					//XXX: see https://webhint.io/docs/user-guide/hints/hint-apple-touch-icons/
					//XXX: see https://developer.apple.com/library/archive/documentation/AppleApplications/Reference/SafariWebContent/ConfiguringWebApplications/ConfiguringWebApplications.html
					//iPhone Retina
					180 => '@RapsysAir/png/icon.180.png',
					//iPad Retina touch icon
					167 => '@RapsysAir/png/icon.167.png',
					//iPad touch icon
					152 => '@RapsysAir/png/icon.152.png',

					//For windows
					//XXX: see https://docs.microsoft.com/en-us/previous-versions/windows/internet-explorer/ie-developer/platform-apis/dn255024(v=vs.85)
					310 => '@RapsysAir/png/icon.310.png',
					150 => '@RapsysAir/png/icon.150.png',
					70 => '@RapsysAir/png/icon.70.png'
				],
				'svg' => '@RapsysAir/svg/icon.svg',
				'title' => 'Libre Air',
				'url' => 'rapsys_air'
			],
			'copy' => [
				'long' => 'All rights reserved',
				'short' => 'Copyright 2019'
			],
			'contact' => [
				'name' => 'John Doe',
				'mail' => 'contact@example.com'
			],
			'locale' => '%kernel.default_locale%',
			'locales' => '%kernel.translator.fallbacks%',
			'languages' => '%rapsys_user.languages%',
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
							->scalarNode('ico')->cannotBeEmpty()->defaultValue($defaults['site']['ico'])->end()
							->scalarNode('logo')->cannotBeEmpty()->defaultValue($defaults['site']['logo'])->end()
							->arrayNode('png')
								->treatNullLike([])
								->defaultValue($defaults['site']['png'])
								->scalarPrototype()->end()
							->end()
							->scalarNode('svg')->cannotBeEmpty()->defaultValue($defaults['site']['svg'])->end()
							->scalarNode('title')->cannotBeEmpty()->defaultValue($defaults['site']['title'])->end()
							->scalarNode('url')->cannotBeEmpty()->defaultValue($defaults['site']['url'])->end()
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
					->scalarNode('locale')->cannotBeEmpty()->defaultValue($defaults['locale'])->end()
					->scalarNode('locales')->cannotBeEmpty()->defaultValue($defaults['locales'])->end()
					->scalarNode('languages')->cannotBeEmpty()->defaultValue($defaults['languages'])->end()
					/*->arrayNode('languages')
						->treatNullLike([])
						->defaultValue($defaults['languages'])
						->scalarPrototype()->end()
					->end()*/
				->end()
			->end();

		return $treeBuilder;
	}
}
