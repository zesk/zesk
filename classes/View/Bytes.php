<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Bytes.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

class View_Bytes extends View {
	function render() {
		$v = $this->value();
		if (empty($v)) {
			$result = $this->empty_string();
		} else {
			$result = Number::format_bytes($v);
		}
		return $result;
	}
}

