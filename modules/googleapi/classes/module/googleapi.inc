<?php
use zesk\Exception_Directory_NotFound;
class Module_GoogleAPI extends zesk\Module {
	public function initialize() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$path = $this->application->path("vendor/google/apiclient");
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$zesk->classes->register("zesk\\Service_Translate_Google");
		parent::initialize();
	}
}
