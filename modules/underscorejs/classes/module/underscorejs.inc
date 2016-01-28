<?php
class Module_UnderscoreJS extends Module {
	public static function hooks() {
		zesk::add_hook("<head>", __CLASS__ . '::head');
	}
	public static function head(Request $request, Response_HTML $response) {
		$response->cdn_javascript('/share/underscorejs/underscore.js', array(
			"weight" => 'first', 
			'share' => true
		));
	}
}