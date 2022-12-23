<?php declare(strict_types=1);
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
		return $this->application->modelFactory(Model_Settings::class);
	}

	protected function initialize(): void {
		$locale = $this->locale();
		if (!$this->submit_url() && $this->request) {
			$this->submit_url($this->request->uri());
		}
		if (!$this->submit_message()) {
			$this->submit_message($locale->__('Your changes have been saved.'));
		}
		$title = $this->title();
		if ($title) {
			$this->setTitle($locale->__($title));
		}
		parent::initialize();
	}

	protected function hook_initialized(): void {
		foreach ($this->all_children() as $child) {
			if ($child->optionBool('settings_ignore')) {
				$this->object->ignore_variable($child->name());
			} else {
				$this->object->allow_variable($child->name());
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::themeVariables()
	 */
	public function themeVariables(): array {
		return [
			'column_count_label' => $this->column_count_label,
			'column_count_widget' => $this->column_count_widget,
		] + parent::themeVariables();
	}
}
