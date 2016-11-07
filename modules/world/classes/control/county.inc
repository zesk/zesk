<?php

class Control_County extends Control_Select_Object {

	protected $class = "County";

	protected $options = array(
		'text_column' => 'name',
		'id_column' => 'id'
	);

	protected function initialize() {
		if (!$this->has_option('noname')) {
			$this->noname(__('Control_County:=No county'));
		}
		parent::initialize();
	}
}
