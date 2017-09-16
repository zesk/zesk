<?php
namespace zesk;

class Control_Country extends Control_Select_Object {
	protected $class = "zesk\\Country";
	protected $options = array(
		'text_column' => 'name',
		'id_column' => 'id',
		'escape_values' => false
	);
	protected function initialize() {
		$this->noname(__('Control_Country:=All countries'));
		$this->set_option('preferred_title', __('Control_Country:=Local'));
		$this->set_option('unpreferred_title', __('Control_Country:=All Countries'));
		parent::initialize();
	}
}
