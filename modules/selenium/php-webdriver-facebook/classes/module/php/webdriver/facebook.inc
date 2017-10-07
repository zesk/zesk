<?php
namespace zesk;

class Module_PHP_WebDriver_Facebook extends Module {
	private $lib_path = null;
	public function initialize() {
		$this->lib_path = $this->application->path("vendor/php-webdriver-facebook");
		if (!is_dir($this->lib_path)) {
			throw new Exception_Directory_NotFound($this->lib_path, "Initializing selenium/php-webdriver-facebook");
		}
		$init = path($this->lib_path, '__init__.php');
		if (!is_file($init)) {
			throw new Exception_File_NotFound($init, "Initializing selenium/php-webdriver-facebook");
		}
		require_once $init;
	}
}

