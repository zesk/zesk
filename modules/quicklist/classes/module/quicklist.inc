<?php declare(strict_types=1);
class Module_QuickList extends zesk\Module_JSLib {
	protected $javascript_paths = [
		"/share/quicklist/js/jquery.quicklist.js" => [
			"share" => true,
		],
	];
}
