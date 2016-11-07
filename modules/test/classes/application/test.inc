<?php
class Application_Test extends Application {
	/**
	 * 
	 * @var unknown
	 */
	public $file = __FILE__;
	/**
	 * 
	 * @var array
	 */
	public $load_modules = array(
		"Bootstrap",
		"Test"
	);
	
	/**
	 * 
	 * @param zesk\Request $request
	 * @param Response $response
	 */
	public function page(zesk\Request $request, Response $response) {
		$response->content = "Test site";
	}
}
