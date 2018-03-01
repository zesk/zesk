<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:31:34 EDT 2008
 */
namespace zesk;

class Control_Link_Object extends Control {
	
	/**
	 *
	 * @var Widget
	 */
	private $widget = null;
	
	/**
	 *
	 * @var array of Model
	 */
	private $models = array();
	
	/**
	 *
	 * @var array of Widget
	 */
	private $widgets = array();
	private function _list_name() {
		return $this->name() . '_list';
	}
	function initialize() {
		$widget = $this->widget;
		$list_name = $this->_list_name();
		$widget->name($list_name . "[]");
		$widget->initialize();
		$this->object = $widget->model();
		
		$values = $this->request->geta($list_name);
		$n = 0;
		$max_objects = $this->maximum_objects();
		foreach ($values as $index => $value) {
			$w = clone $widget;
			$w->object = $widget->model();
			$this->widgets[$index] = $w->name($list_name)->request_index($index);
			++$n;
			if ($n >= $max_objects) {
				break;
			}
		}
		$this->children($this->widgets);
		parent::initialize();
	}
	public function minimum_objects($set = null) {
		if ($set !== null) {
			$this->set_option('minimum_objects', intval($set));
			return $this;
		}
		return $this->option_integer("minimum_objects", 0);
	}
	public function maximum_objects($set = null) {
		if ($set !== null) {
			$this->set_option('maximum_objects', intval($set));
			return $this;
		}
		return $this->option_integer("maximum_objects", 100);
	}
	
	// 	function load() {
	// 		$list_name = $this->name() . '_list';
	
	// 		$values = $this->request->geta($list_name, array());
	// 		foreach ($values as $index => $value) {
	// 			$this->widgets[$index]->load($this->models[$index]);
	// 		}
	// 		$this->value($this->models);
	// 		parent::load();
	// 	}
	function widget(Widget $widget = null) {
		if ($widget === null) {
			return $this->widget;
		}
		$this->widget = $widget;
		$this->widget->object($widget->model());
		return $this;
	}
	function theme_variables() {
		$list_name = $this->name() . "_list";
		return array(
			'link_widgets' => $this->widgets,
			'link_widget' => $this->widget,
			'link_widget_name' => $list_name,
			'minimum_objects' => $this->minimum_objects(),
			'maximum_objects' => $this->maximum_objects(),
			'link_values' => $this->object->get($list_name)
		) + parent::theme_variables();
	}
}

