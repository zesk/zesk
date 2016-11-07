<?php
/**
 * Abstraction for widgets which contain multiple other widgets
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/widgets.inc $
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Control_Widgets extends Control {
	
	/**
	 * Override this method to do a custom widget list for an object
	 *
	 * @return array
	 */
	protected function _widgets() {
		return array();
	}
	private $init_once = false;
	protected $widgets = null;
	protected $render_children = false;
	protected function initialize() {
		assert($this->init_once === false);
		if ($this->init_once) {
			return;
			backtrace();
		}
		$this->widgets = $widgets = $this->widget_factory("Control")->names($this->name() . "_widgets");
		$child_widgets = $this->_widgets();
		if (count($child_widgets)) {
			$this->child($widgets);
			$widgets->children($child_widgets);
		}
		$this->children = $this->call_hook_arguments("children", array(
			$this->children
		), $this->children);
		foreach ($this->children as $name => $child) {
			/* @var $child Widget */
			$child->parent($this);
		}
		$this->init_once = true;
		parent::initialize();
	}
	protected function validate() {
		$valid = true;
		foreach ($this->children as $w) {
			/* @var $w Widget */
			if (/*$w->check_condition($object)*/true) {
				$r = $w->validate();
				if (!$r) {
					$__ = array(
						"name" => $w->name,
						"error" => _dump($w->error())
					);
					$message = "Widget {name} failed validation: {error}";
					log::debug($message, $__);
					$w->error(__($message, $__), $w->column() . "_raw");
					$valid = false;
					$this->error($w);
				}
			}
		}
		if (!$valid && !$this->required()) {
			return true;
		}
		return $valid;
	}
	function before_store() {
		$stored = array();
		foreach ($this->children as $k => $w) {
			/* @var $w Widget */
			if ($w->is_visible() && method_exists($w, "before_store")) {
				if (!$w->before_store()) {
					foreach ($stored as $k => $w) {
						if (method_exists($w, "after_store")) {
							$w->after_store(false);
						}
					}
					return false;
				}
				if (method_exists($w, "after_store")) {
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
	function after_store($succeeded = true) {
		foreach ($this->children as $w) {
			/* @var $w Widget */
			if ($w->is_visible() && method_exists($w, "after_store")) {
				$w->after_store($succeeded);
			}
		}
	}
}
