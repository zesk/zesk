<?php
declare(strict_types=1);
namespace zesk;

class Module_PHPUnit extends Module {
	public function initialize(): void {
		$this->application->autoloader->no_exception = true;
		parent::initialize();
	}
}
