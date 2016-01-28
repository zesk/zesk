<?php

class Module_GoogleAPI extends Module {

	public function initialize() {
		$path = path($this->path, 'lib/src/Google');
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$autoload = path($path, 'autoload.php');
		if (!is_file($autoload)) {
			throw new Exception_File_NotFound($autoload);
		}
		zesk::autoload_path(path($this->path, 'lib/src'), array(
			"lower" => false,
			'extensions' => 'php'
		));
		zesk::register_class("zesk\Service_Translate_Google");
		parent::initialize();
	}
}