<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/XML_RPC_Value.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

class Value_Test extends \zesk\Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	function test_basics() {
		$data = "123";
		$type = Value::type_integer;
		$x = new Value($data, $type);
		
		$x->toXML();
	}
}
