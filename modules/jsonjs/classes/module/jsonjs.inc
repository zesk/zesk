<?php
class Module_JSONJS extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->javascript("/share/jsonjs/json2.js", null, array(
			'share' => true
		));
	}
}
