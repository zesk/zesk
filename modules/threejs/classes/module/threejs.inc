<?php
class Module_ThreeJS extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->javascript("/share/threejs/three.js", array(
			"share" => true
		));
	}
}
