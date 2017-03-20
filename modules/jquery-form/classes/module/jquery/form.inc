<?php
class Module_jQuery_Form extends zesk\Module {
	public static function head(zesk\Request $request, Response $response) {
		$response->jquery();
		$response->cdn_javascript("/share/jquery-form/jquery.form.js", array(
			"share" => true
		));
	}
}
