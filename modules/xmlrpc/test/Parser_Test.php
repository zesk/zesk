<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/xml_rpc_parser_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Test_Unit;

class Parser_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	function test_main() {
		$x = new Parser('<?xml version="1.0" ?>
<methodCall>
<methodName>ruler.authenticate</methodName>
<params>
<param><value><int>0</int></value></param>
<param><value><int>1</int></value></param>
<param><value><string>123123123</string></value></param>
<param><value><dateTime.iso8601>20080811T12:34:22</dateTime.iso8601></value></param>
</params>
</methodCall>');
		
		$result = $x->parse();
		
		$this->assert(is_array($result->Parameters), 'is_array($result->Parameters)');
		$this->assert(count($result->Parameters) === 4, 'count($result->Parameters) === 3');
		$this->assert($result->Parameters[0] === 0, '$result->Parameters[0] === 0');
		$this->assert($result->Parameters[1] === 1, '$result->Parameters[1] === 1');
		$this->assert($result->Parameters[2] === "123123123", '$result->Parameters[2] === "123123123"');
		$this->assert_instanceof($result->Parameters[3], "zesk\Timestamp");
		$dt = $result->Parameters[3];
		
		$this->assert_equal(strval($dt), "2008-08-11 12:34:22");
	}
}
