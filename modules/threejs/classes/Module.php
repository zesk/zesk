<?php declare(strict_types=1);
/**
 * @package zesk-modules
 * @subpackage ThreeJS
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\ThreeJS;

use zesk\Module_JSLib;

class Module extends Module_JSLib {
	protected $javascript_paths = [
		'/share/threejs/three.js' => [
			'share' => true,
		],
	];
}
