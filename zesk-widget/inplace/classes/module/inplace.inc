<?php declare(strict_types=1);
namespace zesk;

class Module_InPlace extends Module_JSLib {
	/**
	 * Array of css paths for this page
	 *
	 * @var array
	 */
	protected $css_paths = [
		'/share/inplace/css/inplace.css' => [
			'share' => true,
		],
	];

	/**
	 * Array of strings of JS to load, or array of path (key) => $options to load
	 *
	 * @var array
	 */
	protected $javascript_paths = [
		'/share/inplace/js/jquery.inplace.js' => [
			'share' => true,
		],
	];
}
