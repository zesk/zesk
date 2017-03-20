<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Radio.php $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Control_Radio extends Control_Options {
	private function _check_refresh() {
		if ($this->option_bool("refresh")) {
			if ($this->request->get($this->name() . "_cont")) {
				$this->message($this->column(), $this->option("continue_message", "Form was updated"));
				return false;
			}
		}
		return true;
	}

	function validate() {
		$name = $this->name();
		$v = $this->request->get($name, $this->option("default", null));
		$opts = $this->option_array("options", array());
		if (array_key_exists($v, $opts)) {
			$this->value($v);
		}
		if (!$this->_check_refresh()) {
			return false;
		}
		return $this->validate_required();
	}

	/**
	 * Return the jQuery expression to determine the value of this widget
	 */
	public function jquery_value_expression() {
		if ($this->has_option('value_expression')) {
			return $this->option('value_expression');
		}
		$name = $this->name();
		if (!$name) {
			return null;
		}
		return "\$(\"input[name=$name]:checked\").val()";
	}
	/**
	 * Return the jQuery expression to determine the value of this widget
	 */
	public function jquery_value_selected_expression() {
		if ($this->has_option('value_selected_expression')) {
			return $this->option('value_selected_expression');
		}
		$name = $this->name();
		if (!$name) {
			return null;
		}
		return "\$(\"input[name=$name]:checked\")";
	}
	/**
	 * Return the jQuery expression to determine the value of this widget
	 */
	public function jquery_target_expression() {
		if ($this->has_option('jquery_target_expression')) {
			return $this->option('jquery_target_expression');
		}
		$name = $this->name();
		if (!$name) {
			return null;
		}
		return "\$(\"input[name=$name]\")";
	}
}
