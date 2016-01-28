<?php
class Application_Test extends Application {

	public $file = __FILE__;

	public function page(Request $request, Response $response) {
		$response->content = "Test site";
	}

	function preconfigure(array $options = array()) {
		zesk::application_class('Application_Test');
		self::configuration_path(zesk::application_root('etc'));
		Module::load('bootstrap');
		Module::load('test');
		parent::configure($options);
	}
}
