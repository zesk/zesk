<?php
class Module_ThreeJS extends Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->cdn_javascript("/share/threejs/three.js", array(
			"share" => true
		));
	}
}
