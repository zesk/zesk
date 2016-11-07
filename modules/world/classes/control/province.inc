<?php

class Control_Province extends Control_Select_Object {
	protected $class = "Province";

	protected $options = array(
		'text_column' => 'name',
		'id_column' => 'id'
	);

	protected function initialize() {
		$this->options['noname'] = __('Control_Province:=All states');
		parent::initialize();
	}

}
