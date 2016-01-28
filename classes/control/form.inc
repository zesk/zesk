<?php
class Control_Form extends Control {
	/**
	 * Theme file to use
	 *
	 * @var string
	 */
	protected $theme = "control/form";

	/**
	 * Set up widgets
	 *
	 * @see Control::initialize($object)
	 */
	function initialize() {
		$form_options = arr::kunprefix($this->options_include("action;method;enctype;form_name;form_id;id;name"), "form_");
		if (!array_key_exists("id", $form_options)) {
			$form_options['id'] = "form-" . md5(microtime());
		}
		if (!array_key_exists("name", $form_options)) {
			$form_options['name'] = $form_options['id'];
		}
		$this->wrap("form", $form_options);
	}
}
