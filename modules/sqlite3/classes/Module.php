<?php
namespace sqlite3;

class Module extends \zesk\Module {
	function initialize() {
		$this->application->register_class(__NAMESPACE__ . "\\Database");
	}
}
