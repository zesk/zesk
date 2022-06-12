<?php declare(strict_types=1);
/**
 * Abstraction for widgets which contain multiple other widgets
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Control_Widgets extends Control {
	/**
	 * Override this method to do a custom widget list for an object
	 *
	 * @return array
	 */
	protected function _widgets() {
		return [];
	}

	private $init_once = false;

	protected $widgets = null;

	protected $render_children = false;

	protected function initialize(): void {
		assert($this->init_once === false);
		if ($this->init_once) {
			return;
		}
		$this->widgets = $widgets = $this->widgetFactory(Control::class)->names($this->name() . '_widgets');
		$child_widgets = $this->_widgets();
		if (count($child_widgets)) {
			$this->addChild($widgets);
			$widgets->children($child_widgets);
		}
		$this->children = $this->call_hook_arguments('children', [
			$this->children,
		], $this->children);
		foreach ($this->children as $name => $child) {
			/* @var $child Widget */
			$child->parent($this);
		}
		$this->init_once = true;
		parent::initialize();
	}

	protected function validate(): bool {
		$valid = true;
		foreach ($this->children as $w) {
			/* @var $w Widget */
			$r = $w->validate();
			if (!$r) {
				if ($this->optionBool('generate_child_errors')) {
					$__ = [
						'name' => $w->name,
						'error' => _dump($w->error()),
					];
					$message = 'Widget {name} failed validation: {error}';
					$this->application->logger->debug($message, $__);
					$w->error($this->application->locale->__($message, $__), $w->column() . '_raw');
				}
				$valid = false;
				$this->error($w);
			}
		}
		if (!$valid && !$this->required()) {
			return true;
		}
		return $valid;
	}

	public function before_store() {
		$stored = [];
		foreach ($this->children as $k => $w) {
			/* @var $w Widget */
			if ($w->is_visible() && method_exists($w, 'before_store')) {
				if (!$w->before_store()) {
					foreach ($stored as $k => $w) {
						if (method_exists($w, 'after_store')) {
							$w->after_store(false);
						}
					}
					return false;
				}
				if (method_exists($w, 'after_store')) {
					$stored[$k] = $w;
				}
			}
		}
		return true;
	}

	// 	function submit() {
	// 		foreach ($this->children as $w) {
	// 			/* @var $w Widget */
	// 			if ($w->is_visible()) {
	// 				$w->submit();
	// 			}
	// 		}
	// 	}
	public function after_store($succeeded = true): void {
		foreach ($this->children as $w) {
			/* @var $w Widget */
			if ($w->is_visible() && method_exists($w, 'after_store')) {
				$w->after_store($succeeded);
			}
		}
	}
}
