<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/checkbox.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:25:31 EDT 2010 21:25:31
 */
class View_Checkbox extends View {

	function render() {
		$true_value = $this->option("true_value", $this->option("truevalue", true));
		//		$false_value	= $this->option("false_value", $this->option("falsevalue", true));
		$ts = $this->option("true_string", $this->option("truestring", "Yes"));
		$fs = $this->option("false_string", $this->option("falsestring", "No"));
		$v = $this->value();
		if ($this->option_bool("null_check")) {
			if ($true_value === null) {
				$is_true = ($v === null);
			} else {
				$is_true = ($v !== null);
			}
		} else {
			$is_true = to_bool($v) == to_bool($true_value);
		}
		$result = $is_true ? $ts : $fs;
		return $this->object->apply_map($result);
	}
}
