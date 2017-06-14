<?php
namespace zesk;

class Module_Git extends Module_Repository {
	function initialize() {
		parent::initialize();
		$this->register_repository("zesk\\Repository_Git", array(
			"git"
		));
	}
}