<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Control_Radio extends Control_Optionss {
	private function _check_refresh() {
		if ($this->optionBool('refresh')) {
			if ($this->request->get($this->name() . '_cont')) {
				$this->message($this->column(), $this->option('continue_message', 'Form was updated'));
				return false;
			}
		}
		return true;
	}

	public function validate() {
		$name = $this->name();
		$v = $this->request->get($name, $this->option('default', null));
		$opts = $this->optionArray('options', []);
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
		if ($this->hasOption('value_expression')) {
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
	public function jquery_value_selected_expression(): string {
		if ($this->hasOption('value_selected_expression')) {
			return $this->option('value_selected_expression');
		}
		$name = $this->name();
		if (!$name) {
			return '';
		}
		return "\$(\"input[name=$name]:checked\")";
	}

	/**
	 * Return the jQuery expression to determine the value of this widget
	 */
	public function jquery_target_expression(): string {
		if ($this->hasOption('jquery_target_expression')) {
			return $this->option('jquery_target_expression');
		}
		$name = $this->name();
		if (!$name) {
			return '';
		}
		return "\$(\"input[name=$name]\")";
	}
}
