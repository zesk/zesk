<?php

class Module_Modernizr extends Module {
	public static function hooks() {
		zesk::add_hook('<head>', __CLASS__ . "::head");
	}

	public static function head(Request $request, Response $response) {
		$response->javascript("/share/modernizr/modernizr.js");
	}
}
