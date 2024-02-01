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
				'donate' => 'https://paypal.me/milongaraphael',
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
					//iOS7
					120 => '@RapsysAir/png/icon.120.png',

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
			'cache' => [
				'namespace' => 'airlibre',
				'lifetime' => 0
			],
			'calendar' => [
				'calendar' => '%env(string:RAPSYSAIR_CALENDAR)',
				'prefix' => '%env(string:RAPSYSAIR_PREFIX)',
				'project' => '%env(string:RAPSYSAIR_PROJECT)',
				'client' => '%env(string:GOOGLE_CLIENT_ID)',
				'secret' => '%env(string:GOOGLE_CLIENT_SECRET)'
			],
			'copy' => [
				'by' => 'Rapsys',
				'link' => 'https://rapsys.eu',
				'long' => 'All rights reserved',
				'short' => 'Copyright 2019-2021',
				'title' => 'Rapsys'
			],
			'contact' => [
				'title' => 'Libre Air',
				'mail' => 'contact@airlibre.eu'
			],
			'facebook' => [
				'apps' => [3728770287223690],
				'height' => 630,
				'width' => 1200
			],
			'locale' => '%kernel.default_locale%',
			'locales' => '%kernel.translator.fallbacks%',
			'languages' => '%rapsys_user.languages%',
			'path' => [
				'cache' => '%kernel.project_dir%/var/cache',
				'public' => dirname(__DIR__).'/Resources/public'
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
							->scalarNode('donate')->cannotBeEmpty()->defaultValue($defaults['site']['donate'])->end()
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
					->arrayNode('cache')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('namespace')->defaultValue($defaults['cache']['namespace'])->end()
							->integerNode('lifetime')->min(0)->defaultValue($defaults['cache']['lifetime'])->end()
						->end()
					->end()
					->arrayNode('calendar')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('calendar')->defaultValue($defaults['calendar']['calendar'])->end()
							->scalarNode('prefix')->defaultValue($defaults['calendar']['prefix'])->end()
							->scalarNode('project')->defaultValue($defaults['calendar']['project'])->end()
							->scalarNode('client')->defaultValue($defaults['calendar']['client'])->end()
							->scalarNode('secret')->defaultValue($defaults['calendar']['secret'])->end()
						->end()
					->end()
					->arrayNode('copy')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('by')->defaultValue($defaults['copy']['by'])->end()
							->scalarNode('link')->defaultValue($defaults['copy']['link'])->end()
							->scalarNode('long')->defaultValue($defaults['copy']['long'])->end()
							->scalarNode('short')->defaultValue($defaults['copy']['short'])->end()
							->scalarNode('title')->defaultValue($defaults['copy']['title'])->end()
						->end()
					->end()
					->arrayNode('contact')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('title')->cannotBeEmpty()->defaultValue($defaults['contact']['title'])->end()
							->scalarNode('mail')->cannotBeEmpty()->defaultValue($defaults['contact']['mail'])->end()
						->end()
					->end()
					->arrayNode('facebook')
						->addDefaultsIfNotSet()
						->children()
							->arrayNode('apps')
								->treatNullLike([])
								->defaultValue($defaults['facebook']['apps'])
								->scalarPrototype()->end()
							->end()
							->integerNode('height')->min(0)->defaultValue($defaults['facebook']['height'])->end()
							->integerNode('width')->min(0)->defaultValue($defaults['facebook']['width'])->end()
						->end()
					->end()
					->scalarNode('locale')->cannotBeEmpty()->defaultValue($defaults['locale'])->end()
					->scalarNode('locales')->cannotBeEmpty()->defaultValue($defaults['locales'])->end()
					->scalarNode('languages')->cannotBeEmpty()->defaultValue($defaults['languages'])->end()
					->arrayNode('path')
						->addDefaultsIfNotSet()
						->children()
							->scalarNode('cache')->defaultValue($defaults['path']['cache'])->end()
							->scalarNode('public')->defaultValue($defaults['path']['public'])->end()
						->end()
					->end()
				->end()
			->end();

		return $treeBuilder;
	}
}
