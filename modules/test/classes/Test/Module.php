<?php
namespace zesk;

class Test_Module extends Module {
	/**
	 *
	 * @var string
	 */
	private $phpunit = null;
	
	/**
	 */
	public function initialize() {
		parent::initialize();
		$this->phpunit = $this->application->application_root("vendor/bin/phpunit");
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function has_phpunit() {
		return file_exists($this->phpunit) && is_executable($this->phpunit);
	}
}
