<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/static.inc $
 * @package zesk
 * @subpackage view
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:09:20 EDT 2010 21:09:20
 */
class View_Static extends View_Text {

	public function text($set = null) {
		return $set !== null ? $this->set_option('text', $set) : $this->option('text');
	}
	function render() {
		$this->value($this->option("text", ""));
		return parent::render();
	}
}
