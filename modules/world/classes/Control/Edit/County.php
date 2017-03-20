<?php
namespace zesk;

class Control_Edit_County extends Control_Edit {
	protected $options = array();
	protected $class = "zesk\\County";
	protected function initialize() {
		parent::initialize();
		$this->options['submit_redirect_message'] = __('"{object.name}" was saved.');
	}
	protected function hook_widgets() {
		$ww[] = $this->widget_factory("Control_Text")
			->names('name', __('Name'))
			->add_class("input-lg")
			->required(true);
		$ww[] = $w = $this->widget_factory('Control_Province')->names('province', __('Province:=State'));
		$ww[] = $w = $this->widget_factory('Control_Button')
			->names('ok', __('Save changes'))
			->nolabel(true)
			->add_class('btn btn-primary');
		$ww[] = $this->widget_factory('Control_Button_Delete')->wrap('div', '.form-group')->nolabel(true);
		
		return $ww;
	}
}
