<?php declare(strict_types=1);
namespace zesk;

class Control_Form extends Control {
	/**
	 * Set up widgets
	 *
	 * @see Control::initialize($object)
	 */
	public function initialize(): void {
		$form_options = ArrayTools::kunprefix($this->options_include('action;method;enctype;form_name;form_id;id;name'), 'form_');
		if (!array_key_exists('id', $form_options)) {
			$form_options['id'] = 'form-' . md5(microtime());
		}
		if (!array_key_exists('name', $form_options)) {
			$form_options['name'] = $form_options['id'];
		}
		$this->wrap('form', $form_options);
	}
}
