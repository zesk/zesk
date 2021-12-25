<?php declare(strict_types=1);
namespace zesk;

class Module_Widget extends Module {
	public function initialize(): void {
		parent::initialize();
		$this->application->register_factory("widget", Widget::class . "::factory");
	}
}
