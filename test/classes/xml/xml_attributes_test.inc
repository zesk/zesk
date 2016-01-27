<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/xml/xml_attributes_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class XML_Attributes_Test extends Test_Unit {
	function test_main() {
		$attributes = array();
		$x = new XML_Attributes($attributes);
	}
}
echo basename(__FILE__) . ": success\n";
