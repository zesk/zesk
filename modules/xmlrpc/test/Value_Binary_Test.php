<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/value/XML_RPC_Value_Binary.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

use xmlrpc\Value_Binary;

class Value_Binary_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	public function test_basics() {
		$data = "Hello, World";
		$isXML = false;
		
		$x = new Value_Binary($data, $isXML);
		
		$x->toXML();
		
		$value = null;
		$x->fromXML($value);
	}
}

