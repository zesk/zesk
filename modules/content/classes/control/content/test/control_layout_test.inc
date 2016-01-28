<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/control/content/test/control_layout_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
class Control_Layout_Test extends Test_Control {
	function initialize() {
		Module::load('content');
	}
	function test_main() {
		$this->test_basics(widget::factory("Control_Content_Layout", array()));

		$options = false;
		$testx = new Control_Content_Layout($options);

		$objects = array();
		$testx->objects($objects);

		$menuControl = new Content_Factory();
		$testx->content_factory($menuControl);

		echo basename(__FILE__) . ": success\n";
	}
}
