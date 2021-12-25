<?php declare(strict_types=1);
namespace zesk;

class Control_Role extends Control_Select_ORM {
	protected $class = "Role";

	protected $options = [
		"id_column" => "id",
		"text_column" => "name",
	];

	protected function initialize(): void {
		$this->options['noname'] = $this->application->locale->__("All roles");
		$this->options['where'] = [
			'X.is_default' => false,
		];
		$this->options['translate_after'] = true;
		parent::initialize();
	}
}
