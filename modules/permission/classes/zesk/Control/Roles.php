<?php
namespace zesk;

class Control_Roles extends Control_Checklist_Object {
	/**
	 * 
	 * @var string
	 */
	protected $class = "zesk\\Role";
	public function initialize() {
		parent::initialize();
		
		if (!$this->user_can("zesk\\Role::view_all")) {
			$this->options['where'] = array(
				"OR" => array(
					"X.visibility" => 1
				)
			);
		}
	}
}
