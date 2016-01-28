<?php
class Module_JSONJS extends Module {
	public static function head(Request $request, Response_HTML $response) {
		$response->javascript("/share/jsonjs/json2.js", null, array(
			'share' => true
		));
	}
}
