<?php

/**
 * Simple module to allow for conditions within Widgets to work
 *
 * One method simply uses a column within the object and a lookup table to set
 * options for this widget.
 *
 * Another allows the user to set multiple conditions which trigger changes to the options
 * in a widget.
 *
 * @author kent
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Module_Widget_Condition extends Module {
	public function initialize() {
		parent::initialize();
		$this->application->hooks->add(__NAMESPACE__ . "\\Widget::model_changed", 'Module_Widget_Condition::apply');
	}
	public static function apply(Widget $widget, Model $object) {
		self::apply_dynamic_column($widget, $object);
	}
	private static function apply_dynamic_column(Widget $widget, Model $object) {
		if (!$widget->has_option("dynamic_column")) {
			return;
		}
		$col = $widget->column();
		$type = $object->get($widget->option("dynamic_column", $col . "Type"), '');
		$all_options = $widget->option_array("dynamic_options");
		if (!is_array($all_options)) {
			return;
		}
		$options = avalue($all_options, strval($type));
		if (!is_array($options)) {
			return;
		}
		$widget->set_option($options);
	}
	private function condition_setup(Widget $widget, Model $object) {
		if (!$widget->has_option("condition_options")) {
			return;
		}
		$conditions = $widget->option_array("condition_options");
		if (!is_array($conditions)) {
			return;
		}
		$result = false;
		foreach ($conditions as $condition => $options) {
			$condition = $object->apply_map($condition);
			$result = self::condition_evaluate($condition);
			$widget->set_option('condition_result', $result);
			if ($result) {
				$widget->set_option($options);
			}
		}
	}
	
	/**
	 * Evaluate a condition
	 *
	 * @param $condition string
	 *        	Condition string
	 * @param $default mixed
	 *        	The value to return if the condition does not parse
	 * @return unknown
	 */
	public static function condition_evaluate($condition, $default = false) {
		if (is_array($condition)) {
			foreach ($condition as $cond) {
				if (self::condition_evaluate($cond, $default) === true) {
					return true;
				}
			}
			return $default;
		}
		$conds = array(
			"<=",
			">=",
			"!=",
			"!=",
			"<",
			">",
			"=",
			"!"
		);
		foreach ($conds as $c) {
			if (strpos($condition, $c) !== false) {
				$cond = explode($c, $condition, 2);
				switch ($c) {
					case "<=":
						return ($cond[0] <= $cond[1]);
					case ">=":
						return ($cond[0] >= $cond[1]);
					case "!=":
						return ($cond[0] != $cond[1]);
					case "!=":
						return ($cond[0] != $cond[1]);
					case "<":
						return ($cond[0] < $cond[1]);
					case ">":
						return ($cond[0] > $cond[1]);
					case "=":
						return ($cond[0] == $cond[1]);
					case "!":
						return !to_bool($cond[1], false);
					default :
						throw new Exception_Semantics("Unknown condition found? " . $condition);
				}
			}
		}
		return $default;
	}
}

