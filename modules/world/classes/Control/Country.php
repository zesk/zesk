<?php declare(strict_types=1);
namespace zesk;

class Control_Country extends Control_Select_ORM {
	protected $class = "zesk\\Country";

	protected $options = [
		'text_column' => 'name',
		'id_column' => 'id',
		'escape_values' => false,
	];

	protected function initialize(): void {
		$locale = $this->application->locale;
		$this->noname($locale->__('Control_Country:=All countries'));
		$this->set_option('preferred_title', $locale->__('Control_Country:=Local'));
		$this->set_option('unpreferred_title', $locale->__('Control_Country:=All Countries'));
		parent::initialize();
	}
}
