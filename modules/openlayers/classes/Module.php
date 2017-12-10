<?php

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
	protected $css_paths = array(
		'/share/openlayers/css/ol.css'
	);
	
	/**
	 *
	 * @var array
	 */
	protected $javascript_paths = array(
		'/share/openlayers/build/ol.js'
	);
}
