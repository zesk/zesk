<?php declare(strict_types=1);
namespace zesk;

class Control_Edit_County extends Control_Edit {
	protected $options = [];

	protected $class = __NAMESPACE__ . "\\" . "County";

	protected function initialize(): void {
		parent::initialize();
		$locale = $this->application->locale;
		$this->options['submit_redirect_message'] = $locale->__('"{object.name}" was saved.');
	}

	protected function hook_widgets() {
		$locale = $this->application->locale;
		$ww[] = $this->widget_factory(Control_Text::class)
			->names('name', $locale->__('Name'))
			->add_class("input-lg")
			->required(true);
		$ww[] = $w = $this->widget_factory('Control_Province')->names('province', $locale->__('Province:=State'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Button')
			->names('ok', $locale->__('Save changes'))
			->nolabel(true)
			->add_class('btn btn-primary');
		$ww[] = $this->widget_factory('zesk\\Control_Button_Delete')->wrap('div', '.form-group')->nolabel(true);

		return $ww;
	}
}
