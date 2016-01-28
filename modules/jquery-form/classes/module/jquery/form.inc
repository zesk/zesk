<?php
class Module_jQuery_Form extends Module {
	public static function head(Request $request, Response $response) {
		$response->jquery();
		$response->cdn_javascript("/share/jquery-form/jquery.form.js", array(
			"share" => true
		));
	}
}
