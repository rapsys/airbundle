<?php
// src/Rapsys/AirBundle/Twig/FileGetContentsExtension.php
namespace Rapsys\AirBundle\Twig;

class FileGetContentsExtension extends \Twig_Extension {
	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('file_get_contents', 'file_get_contents', array(false, null))
		);
	}
}
