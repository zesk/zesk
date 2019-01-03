<?php
namespace zesk;

class Control_Province extends Control_Select_ORM {
	protected $class = "zesk\\Province";

	protected $options = array(
		'text_column' => 'name',
		'id_column' => 'id',
	);

	protected function initialize() {
		$this->options['noname'] = __('zesk\\Control_Province:=All states');
		parent::initialize();
	}
}
