<?php
namespace zesk;

class Control_Settings extends Control {
	/**
	 *
	 * @var integer
	 */
	protected $column_count_label = 4;

	/**
	 *
	 * @var integer
	 */
	protected $column_count_widget = 8;

	/**
	 *
	 * @var Model_Settings
	 */
	protected $object = null;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::model()
	 */
	public function model() {
		return $this->application->model_factory(Model_Settings::class);
	}
	protected function initialize() {
		$locale = $this->locale();
		if (!$this->submit_url()) {
			$this->submit_url($this->request->uri());
		}
		if (!$this->submit_message()) {
			$this->submit_message($locale->__('Your changes have been saved.'));
		}
		$title = $this->title();
		if ($title) {
			$this->title($locale->__($title));
		}
		parent::initialize();
	}
	protected function hook_initialized() {
		foreach ($this->all_children() as $child) {
			if ($child->option_bool("settings_ignore")) {
				$this->object->ignore_variable($child->name());
			} else {
				$this->object->allow_variable($child->name());
			}
		}
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::theme_variables()
	 */
	public function theme_variables() {
		return array(
			'column_count_label' => $this->column_count_label,
			'column_count_widget' => $this->column_count_widget
		) + parent::theme_variables();
	}
}
