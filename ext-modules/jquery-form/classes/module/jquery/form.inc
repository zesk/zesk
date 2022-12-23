<?php declare(strict_types=1);
namespace zesk;

class Module_jQuery_Form extends Module_JSLib {
	protected $javascript_paths = [
		'/share/jquery-form/jquery.form.js' => [
			'share' => true,
		],
	];
}
