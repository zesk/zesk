<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/classes/control/ip.inc $
* @package zesk
* @subpackage widgets
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2009, Market Acumen, Inc.
*/
class Control_IP extends Control {

	protected $theme = "control/ip";

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
