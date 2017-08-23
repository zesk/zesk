<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/XML_RPC_Value.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

class XML_RPC_Value_Test extends Test {
	function test_basics() {
		$x = new Value($data, $type);
		
		$x->toXML();
	}
}
