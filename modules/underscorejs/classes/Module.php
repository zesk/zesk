<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\UnderscoreJS;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module_JSLib {
	protected $javascript_paths = [
		'/share/underscorejs/underscore.js' => [
			'weight' => 'first',
		],
	];
}
