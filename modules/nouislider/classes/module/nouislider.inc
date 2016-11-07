<?php

class Module_NoUISlider extends Module {
	public static function head(zesk\Request $request, zesk\Response_Text_HTML $response) {
		$response->jquery();
		$response->cdn_javascript("/share/nouislider/jquery.nouislider.js", array("share" => true));
	}
}
