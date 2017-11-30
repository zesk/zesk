<?php
class Module_NoUISlider extends zesk\Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->jquery();
		$response->javascript("/share/nouislider/jquery.nouislider.js", array(
			"share" => true
		));
	}
}
