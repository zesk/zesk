<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/classes/control/hidden.inc $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2006, Market Acumen, Inc.
*/
class Control_Hidden extends Control {

	public function is_visible() {
		return false;
	}
	public function render() {
		$col = $this->column();
		$input_name = $this->name();

		return html::hidden($this->name(), $this->value(), $this->input_attributes() + $this->data_attributes());
	}
}
