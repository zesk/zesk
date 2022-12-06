<?php declare(strict_types=1);
namespace zesk\JSONJS;

class Module extends \zesk\Module_JSLib {
	protected $javascript_paths = [
		'/share/jsonjs/json2.js' => [
			'share' => true,
		],
	];
}
