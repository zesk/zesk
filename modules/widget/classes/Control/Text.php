<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Text.php $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Wed Aug 27 22:23:05 EDT 2008 22:23:05
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Text extends Control {
	protected function initialize() {
		$this->options += ArrayTools::map_keys($this->options, array(
			"integer_minimum" => "value_minimum",
			"integer_minimum_error" => "value_minimum_error",
			"integer_maximum" => "value_maximum",
			"integer_maximum_error" => "value_maximum_error"
		));
		parent::initialize();
	}
	public function value_minimum($set = null) {
		return $set === null ? $this->option_double('value_minimum') : $this->set_option('value_minimum', doubleval($set));
	}
	public function value_maximum($set = null) {
		return $set === null ? $this->option_double('value_maximum') : $this->set_option('value_maximum', doubleval($set));
	}
	private function _validate_numeric(&$v) {
		$clamp = to_bool($this->option_bool('value_clamp'));
		$min = $this->option_integer("value_minimum", null);
		$max = $this->option_integer("value_maximum", null);
		if ($min !== null && $v < $min) {
			if ($clamp) {
				$v = $min;
			} else {
				$this->error($this->option("value_minimum_error", __("{label} must be at least {value_minimum}.")));
				return false;
			}
		}
		if ($max !== null && $v > $max) {
			if ($clamp) {
				$v = $max;
			} else {
				$this->error($this->option("value_maximum_error", __("{label} must be at most {value_maximum}")));
				return false;
			}
		}
		return true;
	}
	
	/*
	 * Validate an integer
	 */
	function validate_integer() {
		$v = $this->value();
		if (empty($v)) {
			return !$this->required();
		}
		if (!is_numeric($v)) {
			$this->error(__("You must enter a numeric value for {label}."));
			return false;
		}
		if (!$this->_validate_numeric($v)) {
			return false;
		}
		$this->value(intval($v));
		return true;
	}
	function validate_real() {
		$v = $this->value();
		if (empty($v)) {
			return !$this->required();
		}
		if (!empty($v) && !is_numeric($v)) {
			$this->error(__("You must enter a numeric value for {label}."));
			return false;
		}
		$v = doubleval($v);
		if ($this->has_option("decimal_precision")) {
			$v = round($v, $this->option_integer("decimal_precision"));
		}
		if (!$this->_validate_numeric($v)) {
			return false;
		}
		$this->value($v);
		return true;
	}
	public function theme_variables() {
		return array(
			'placeholder' => $this->option('placeholder', $this->label),
			'password' => $this->option_bool('password'),
			'textarea' => $this->textarea(),
			"rows" => $this->option_integer("rows", 3),
			"cols" => $this->option_integer("cols", 60),
			"input_group_addon" => $this->option("input_group_addon", null)
		) + parent::theme_variables();
	}
	protected function validate() {
		if (!parent::validate()) {
			return false;
		}
		if ($this->has_option("validate")) {
			$validate = $this->option("validate");
			$method = "validate_$validate";
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("Unknokn validation method $method for Control_Text");
			}
			$result = $this->$method();
			if (!$result) {
				return $result;
			}
		}
		return $this->validate_size();
	}
	function input_group_addon($set = null, $left = false) {
		if ($set !== null) {
			if ($left) {
				$this->set_option('input_group_addon_left', true);
			} else {
				$this->set_option('input_group_addon_left', null);
			}
			$this->set_option('input_group_addon', $set);
			return $this;
		}
		return $this->option('input_group_addon');
	}
	function textarea($set = null) {
		if ($set !== null) {
			$this->set_option('textarea', $set);
			return $this;
		}
		return $this->option_bool('textarea');
	}
	function rows($set = null) {
		if ($set !== null) {
			$this->textarea(true);
			return $this->set_option('rows', $set);
		}
		return $this->option_integer('rows', null);
	}
}

