<?php
namespace zesk;

class Module_Selenium extends Module {
	public function is_enabled() {
		/* @var $zesk zesk\Kernel */
		return !$this->option_bool("disabled");
	}
}
