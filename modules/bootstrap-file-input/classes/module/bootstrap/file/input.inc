<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/bootstrap-file-input/classes/module/bootstrap/file/input.inc $
 */
class Module_Bootstrap_File_Input extends Module {
	public static function head(Request $request, Response_HTML $response, Template $template) {
		// Lazy eval
		if (zesk::getb('Module_Bootstrap::enabled', $request->getb('Module_Bootstrap::enabled', true))) {
			$response->cdn_javascript(array(
				"/share/bootstrap-file-input/bootstrap.file-input.js"
			), array(
				'share' => true, 
				'weight' => 1
			));
			$response->jquery('$(\'input[type=file]\').bootstrapFileInput();');
		}
	}
}
