<?php declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/bootstrap-file-input/classes/module/bootstrap/file/input.inc $
 */
class Module_Bootstrap_File_Input extends zesk\Module_JSLib {
	protected $javascript_paths = [
		"/share/bootstrap-file-input/bootstrap.file-input.js" => [
			'share' => true,
			'after' => "jquery.js",
		],
	];

	protected $jquery_ready = [
		'$(\'input[type=file]\').bootstrapFileInput();',
	];
}
