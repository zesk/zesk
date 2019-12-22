<?php
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
	protected function initialize() {
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
		$ww[] = $this->widget_factory(Control_Text::class)
			->names('name', $locale->__('Name'))
			->add_class("input-lg")
			->required(true);

		$ww[] = $w = $this->widget_factory(Control_Button::class)
			->names('ok', $locale->__('Save changes'))
			->nolabel(true)
			->add_class('btn btn-primary');
		$ww[] = $this->widget_factory(Control_Button_Delete::class)->wrap('div', '.form-group')->nolabel(true);

		return $ww;
	}
}

//
