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
		return new Model_Settings();
	}
	protected function initialize() {
		if (!$this->submit_url()) {
			$this->submit_url($this->request->uri());
		}
		if (!$this->submit_message()) {
			$this->submit_message(__('Your changes have been saved.'));
		}
		$title = $this->title();
		if ($title) {
			$this->response->title(__($title));
		}
		parent::initialize();
	}
	protected function hook_initialized() {
		foreach ($this->all_children() as $child) {
			if ($child->option_bool("settings_ignore")) {
				$this->object->ignore_variable($child->name());
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
