<?php
use zesk\Response;
use zesk\Request;
class Module_JQuery_File_Upload extends zesk\Module {
	
	/**
	 * Implements Module::head
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public static function head(Request $request, Response $response) {
		$response->jquery();
		$response->javascript("/share/jquery_file_upload/js/jquery.fileupload.js");
	}
	
	/**
	 * Implements Module::routes
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function hook_routes(zesk\Router $router) {
		$router->add_route("jquery_file_upload", array(
			"controller" => "JQuery_File_Upload"
		));
	}
}
