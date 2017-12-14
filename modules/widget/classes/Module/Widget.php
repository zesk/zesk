<?php
namespace zesk;

class Module_Widget extends Module {
	public function initialize() {
		parent::initialize();
		$this->application->register_factory("widget", Widget::class . "::factory");
	}
}