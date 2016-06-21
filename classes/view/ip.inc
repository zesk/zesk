<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/ip.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
class View_IP extends View {

	function render() {
		$v = $this->value();
		return empty($v) ? $this->empty_string() : IPv4::from_integer($v);
	}
}
