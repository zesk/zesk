<?php

namespace zesk;

class Control_Roles extends Control_Checklist_Object {
	/**
	 * 
	 * @var string
	 */
	protected $class = "Role";
	
	public function initialize() {
		parent::initialize();
		
		if (!$this->user_can("Role::view_all")) {
			$this->options['where'] = array(
				"OR" => array(
					"X.visibility" => 1
				)
			);
		}
	}
}
