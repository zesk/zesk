<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\OpenLayers4;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib {
	/**
	 *
	 * @var array
	 */
	protected $css_paths = [
		'/share/openlayers/css/ol.css',
	];

	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = [
		'/share/openlayers/build/ol.js',
	];
}
