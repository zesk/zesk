<?php
namespace dnsmadeeasy;

class Module extends \zesk\Module {
	public function initialize() {
		$this->application->register_class(__NAMESPACE__ . 'Server_Feature_DNS');
	}
}
