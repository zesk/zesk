<?php
namespace zesk;

class Control_Role extends Control_Select_ORM {
	protected $class = "Role";

	protected $options = array(
		"id_column" => "id",
		"text_column" => "name",
	);

	protected function initialize() {
		$this->options['noname'] = __("All roles");
		$this->options['where'] = array(
			'X.is_default' => false,
		);
		$this->options['translate_after'] = true;
		parent::initialize();
	}
}
