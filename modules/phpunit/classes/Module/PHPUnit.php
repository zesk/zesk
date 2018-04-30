<?php
namespace zesk;

class Module_PHPUnit extends Module {
	function initialize() {
		$this->application->autoloader->no_exception = true;
		parent::initialize();
	}
}