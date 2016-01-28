<?php
class Module_ThreeJS extends Module {
	public static function head(Request $request, Response_HTML $response) {
		$response->cdn_javascript("/share/threejs/three.js", array(
			"share" => true
		));
	}
}
