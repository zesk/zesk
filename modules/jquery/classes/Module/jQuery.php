<?php
namespace zesk;

class Module_jQuery extends Module_JSLib {
	public function initialize() {
		$this->application->share_path($this->path . '/share-tools', 'jquery-tools');
	}
}
