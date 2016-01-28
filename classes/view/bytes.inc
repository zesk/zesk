<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/bytes.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
class View_Bytes extends View {
	function render() {
		$v = $this->value();
		if (empty($v)) {
			$result = $this->empty_string();
		} else {
			$result = number::format_bytes($v);
		}
		return $result;
	}
}

