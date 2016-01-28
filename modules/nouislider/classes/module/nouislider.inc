<?php

class Module_NoUISlider extends Module {
	public static function head(Request $request, Response_HTML $response) {
		$response->jquery();
		$response->cdn_javascript("/share/nouislider/jquery.nouislider.js", array("share" => true));
	}
}
