<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Control_Edit_County extends Control_Edit {
	protected $options = [];

	protected $class = __NAMESPACE__ . '\\' . 'County';

	protected function initialize(): void {
		parent::initialize();
		$locale = $this->application->locale;
		$this->options['submit_redirect_message'] = $locale->__('"{object.name}" was saved.');
	}

	protected function hook_widgets() {
		$locale = $this->application->locale;
		$ww[] = $this->widgetFactory(Control_Text::class)
			->names('name', $locale->__('Name'))
			->addClass('input-lg')
			->setRequired(true);
		$ww[] = $w = $this->widgetFactory('Control_Province')->names('province', $locale->__('Province:=State'));
		$ww[] = $w = $this->widgetFactory('zesk\\Control_Button')
			->names('ok', $locale->__('Save changes'))
			->nolabel(true)
			->addClass('btn btn-primary');
		$ww[] = $this->widgetFactory('zesk\\Control_Button_Delete')->addWrap('div', '.form-group')->nolabel(true);

		return $ww;
	}
}
