<?php
class Module_BackboneJS extends Module {
	public static function head(Request $request, Response_HTML $response) {
		Module_UnderscoreJS::head($request, $response);
		$response->cdn_javascript("/share/backbonejs/backbone.js", array(
			"share" => true
		));
	}
}
