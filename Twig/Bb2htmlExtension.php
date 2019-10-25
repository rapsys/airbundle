<?php
// src/Rapsys/AirBundle/Twig/Bb2htmlExtension.php
namespace Rapsys\AirBundle\Twig;

class Bb2htmlExtension extends \Twig_Extension {
	public function getFilters() {
		return array(
			new \Twig\TwigFilter(
				'bb2html',
				function($text) {
					$ctx = bbcode_create(
						array(
							'' => array('type' => BBCODE_TYPE_ROOT),
							'code' => array(
								'type' => BBCODE_TYPE_OPTARG,
								'open_tag' => '<pre class="{PARAM}">',
								'close_tag' => '</pre>',
								'default_arg' => '{CONTENT}'
							),
							'ul' => array(
								'type' => BBCODE_TYPE_NOARG,
								'open_tag' => '<ul>',
								'close_tag' => '</ul>',
								'childs' => 'li'
							),
							'li' => array(
								'type' => BBCODE_TYPE_NOARG,
								'open_tag' => '<li>',
								'close_tag' => '</li>',
								'parent' => 'ul',
								'childs' => 'url'
							),
							'url' => array(
								'type' => BBCODE_TYPE_OPTARG,
								'open_tag' => '<a href="{PARAM}">',
								'close_tag' => '</a>',
								'default_arg' => '{CONTENT}',
								'parent' => 'p,li'
							)
						)
					);
					$text = nl2br(bbcode_parse($ctx, htmlspecialchars($text)));
					if (preg_match_all('#\<pre[^>]*\>(.*?)\</pre\>#s', $text, $matches) && !empty($matches[1])) {
						foreach($matches[1] as $string) {
							$text = str_replace($string, str_replace('<br />', '', $string), $text);
						}
					}
					if (preg_match_all('#\<ul[^>]*\>(.*?)\</ul\>#s', $text, $matches) && !empty($matches[1])) {
						foreach($matches[1] as $string) {
							$text = str_replace($string, str_replace('<br />', '', $string), $text);
						}
					}
					$text = preg_replace(
						array('#(<br />(\r?\n?))*<pre#s', '#</pre>(<br />(\r?\n?))*#', '#(<br />(\r?\n?))*<ul#s', '#</ul>(<br />(\r?\n?))*#', '#(<br />(\r?\n?)){2,}#'),
						array('</p>\2<pre', '</pre>\2<p>', '</p>\2<ul', '</ul>\2<p>', '</p>\2<p>'),
						$text
					);
					return $text;
				},
				array('is_safe' => array('html'))
			)
		);
	}
}
