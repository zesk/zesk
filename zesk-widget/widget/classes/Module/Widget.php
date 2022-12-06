<?php declare(strict_types=1);
namespace zesk;

class Module_Widget extends Module {
	public function initialize(): void {
		parent::initialize();
		$this->application->registerFactory('widget', Widget::class . '::factory');
	}
}
