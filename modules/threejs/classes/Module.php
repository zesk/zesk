<?php
/**
 * @package zesk-modules
 * @subpackage ThreeJS
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\ThreeJS;

use zesk\Module_JSLib;

class Module extends Module_JSLib {
	protected $javascript_paths = array(
		"/share/threejs/three.js" => array(
			"share" => true,
		),
	);
}
