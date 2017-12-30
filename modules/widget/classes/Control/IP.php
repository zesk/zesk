<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/IP.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

class Control_IP extends Control {
	function validate() {
		$value = $this->request->get($this->name());
		if (IPv4::valid($value)) {
			$this->value(ip2long($value));
			return true;
		}
		if ($this->required()) {
			$this->error_required();
			return false;
		}
		return true;
	}
}