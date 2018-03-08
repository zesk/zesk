<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/bootstrap-file-input/classes/module/bootstrap/file/input.inc $
 */
class Module_Bootstrap_File_Input extends zesk\Module_JSLib {
	protected $javascript_paths = array(
		"/share/bootstrap-file-input/bootstrap.file-input.js" => array(
			'share' => true,
			'after' => "jquery.js"
		)
	);
	protected $jquery_ready = array(
		'$(\'input[type=file]\').bootstrapFileInput();'
	);
}
