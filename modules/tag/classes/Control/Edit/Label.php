<?php declare(strict_types=1);
namespace zesk\Tag;

use zesk\Control_Text;
use zesk\Control_Button;
use zesk\Control_Button_Delete;
use zesk\Control_Edit;

/**
 *
 * @author kent
 *
 */
class Control_Edit_Label extends Control_Edit {
	protected $class = Label::class;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Edit::initialize()
	 */
	protected function initialize(): void {
		$locale = $this->application->locale;
		parent::initialize();
		$this->options['submit_redirect_message'] = $locale->__('Tag "{object.name}" was saved.');
	}

	/**
	 *
	 * @return boolean|\zesk\Widget
	 */
	protected function hook_widgets() {
		$locale = $this->application->locale;
		$ww[] = $this->widgetFactory(Control_Text::class)
			->names('name', $locale->__('Name'))
			->addClass('input-lg')
			->setRequired(true);

		$ww[] = $w = $this->widgetFactory(Control_Button::class)
			->names('ok', $locale->__('Save changes'))
			->nolabel(true)
			->addClass('btn btn-primary');
		$ww[] = $this->widgetFactory(Control_Button_Delete::class)->addWrap('div', '.form-group')->nolabel(true);

		return $ww;
	}
}

//
