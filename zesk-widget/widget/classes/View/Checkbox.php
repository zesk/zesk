<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Sun Apr 04 21:25:31 EDT 2010 21:25:31
 */
namespace zesk;

class View_Checkbox extends View {
	public function render(): string {
		$true_value = $this->option('true_value', $this->option('truevalue', true));
		//		$false_value	= $this->option("false_value", $this->option("falsevalue", true));
		$ts = $this->option('true_string', $this->option('truestring', 'Yes'));
		$fs = $this->option('false_string', $this->option('falsestring', 'No'));
		$v = $this->value();
		if ($this->optionBool('null_check')) {
			if ($true_value === null) {
				$is_true = ($v === null);
			} else {
				$is_true = ($v !== null);
			}
		} else {
			$is_true = toBool($v) == toBool($true_value);
		}
		$result = $is_true ? $ts : $fs;
		return $this->object->applyMap($result);
	}
}
