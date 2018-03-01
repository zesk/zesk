<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:07 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Checklist extends Control_Optionss {
	const option_checklist_exclusive = "checklist_exclusive";
	
	/**
	 *
	 * @var array
	 */
	private $widgets_id = null;
	
	/**
	 *
	 * @var array
	 */
	private $checkbox_exclusives = array();
	
	/**
	 * Convert value to/from a string (list)
	 *
	 * @param string $set
	 */
	public function value_is_list($set = null) {
		return is_bool($set) ? $this->set_option('value_is_list', $set) : $this->option_bool('value_is_list');
	}
	
	/**
	 * Getter/setter for columns to display checkboxes in
	 *
	 * @param integer $set
	 * @return integer|self
	 */
	public function columns($set = null) {
		return $set === null ? $this->option_integer('columns') : $this->set_option('columns', intval($set));
	}
	public function checkbox_exclusive($value = null, $set = null) {
		if ($value === null) {
			$result = array();
			foreach ($this->children() as $child) {
				if ($child->option_bool(self::option_checklist_exclusive) === $set) {
					$result[] = $child;
				}
			}
			return $result;
		}
		if (is_scalar($value)) {
			$this->checkbox_exclusives[$value] = $set;
			return $this;
		}
		throw new Exception_Parameter("{method} {name} {id} Widget not support for value {type} {value}", array(
			"method" => __METHOD__,
			"type" => gettype($value),
			"value" => $value
		) + $this->options);
	}
	protected function hook_control_options_changed() {
		$this->widgets_id = null;
		$this->_init_children(to_array($this->control_options));
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Options::initialize()
	 */
	protected function initialize() {
		$options = $this->option('options');
		if (is_array($options)) {
			$this->_init_children($options);
		} else {
			$this->control_options = $this->call_hook_arguments('options', array(), $this->control_options);
			$this->call_hook("control_options_changed");
		}
		parent::initialize();
	}
	private function control_checkbox_factory($name, $col, $label, $value) {
		return $this->widget_factory(Control_Checkbox::class, array(
			'name' => $name . "[]",
			'column' => $col,
			'id' => $col,
			'label_checkbox' => $label,
			'checked_value' => $value
		));
	}
	private function _child_name($value) {
		return "checklist-" . $this->name() . "-$value";
	}
	/**
	 * Add children
	 *
	 * @param array $options
	 */
	protected function _init_children(array $options) {
		if (is_array($this->widgets_id)) {
			return $this->widgets_id;
		}
		$this->widgets_id = array();
		$name = $this->name();
		foreach ($options as $value => $label) {
			$col = $this->_child_name($value);
			$this->widgets_id[$value] = $widget = $this->control_checkbox_factory($name, $col, $label, $value);
			$this->child($col, $widget);
			$exclusive = avalue($this->checkbox_exclusives, $value, null);
			if (is_bool($exclusive)) {
				$widget->set_option(self::option_checklist_exclusive, $exclusive);
			}
		}
		return $this->widgets_id;
	}
	
	/**
	 * Hook intialized
	 */
	protected function hook_initialized() {
		$values = $this->call_hook_arguments("object_value", array(), array());
		if (can_iterate($values)) {
			foreach ($values as $id => $item) {
				if (array_key_exists($item, $this->widgets_id)) {
					$this->widgets_id[$item]->set_option("checked", true);
				}
			}
		}
	}
	
	/**
	 *
	 * @return string
	 */
	private function option_separator() {
		return $this->option('separator', ';');
	}
	
	/**
	 *
	 * @return array Iterator
	 */
	protected function hook_object_value() {
		if ($this->value_is_list()) {
			return ArrayTools::flip_copy(to_list($this->value(), to_list($this->default_value(), array()), $this->option_separator()));
		}
		return $this->value();
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::load()
	 */
	protected function load() {
		$name = $this->name();
		$values = $this->request->geta($name);
		foreach ($values as $value) {
			$child = $this->child($this->_child_name($value));
			if ($child) {
				$child->set_option("checked", true);
			}
		}
		$column = $this->column();
		$this->object->set($column, $values);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submit()
	 */
	public function submit() {
		$values = $this->request->geta($this->name());
		if ($this->value_is_list()) {
			$this->value(implode($this->option_separator(), $values));
		} else {
			$this->value($values);
		}
		return true;
	}
	// Debugging only
	// 	private $debug = "";
	// 	public function render() {
	// 		return parent::render() . $this->debug;
	// 	}
}
