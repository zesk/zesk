<?php
namespace MySQL;

class Module extends \zesk\Module {
	public function initialize() {
		$this->application->register_class("MySQL\\Database");
	}
}
