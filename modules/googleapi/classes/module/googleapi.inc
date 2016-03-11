<?php

class Module_GoogleAPI extends Module {

	public function initialize() {
		$path = $this->application->application_root("vendor/google/apiclient");
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		zesk::register_class("zesk\\Service_Translate_Google");
		parent::initialize();
	}
}