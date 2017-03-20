<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/test/Control_Date_Input_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Control_Date_Input_Test extends Test_Control {
	protected $load_modules = array(
		"date",
	);
	
	function test_main() {
		$this->test_basics(widget::factory('Control_Date_Input'));

		$options = false;
		$testx = new Control_Date_Input($options);
	}
}
