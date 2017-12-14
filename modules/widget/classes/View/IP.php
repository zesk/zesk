<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/IP.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
namespace zesk;

class View_IP extends View {
	function render() {
		$v = $this->value();
		return empty($v) ? $this->empty_string() : IPv4::from_integer($v);
	}
}
