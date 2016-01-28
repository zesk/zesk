<?php
/**
 * $URL:
 * https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/test/Control_Date_Range_Test.inc
 * $
 *
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Control_Date_Range_Test extends Test_Control {
	function initialize() {
		Module::load('date');
	}
	function test_main() {
		$this->test_basics(widget::factory('Control_Date_Range'));

		$options = false;
		$testx = new Control_Date_Range($options);
	}
}
