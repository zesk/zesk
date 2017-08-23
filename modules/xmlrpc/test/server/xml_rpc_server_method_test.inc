<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/server/xml_rpc_server_method_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

use xmlrpc\Server;
use xmlrpc\Server_Method;
use xmlrpc\Exception;

class AddMeUp extends Server {
	function rpc_add($a, $b) {
		return $a + $b;
	}
}
class XML_RPC_Server_Method_Test {
	function test_main() {
		$name = "add.me.up";
		$returnType = "int";
		$phpMethod = "this:add";
		$parameterTypes = array(
			"one" => "int",
			"two" => "int"
		);
		$help = false;
		$parameterHelp = array(
			"one" => "Some number",
			"two" => "another number"
		);
		$x = new Server_Method($name, $returnType, $phpMethod, $parameterTypes, $help, $parameterHelp);
		
		$methodName = "add.me.up";
		$arguments = array(
			1,
			2
		);
		$x->checkArguments($methodName, $arguments);
		
		$object = new AddMeUp();
		$arguments = array(
			1,
			2
		);
		$x->invoke($object, $arguments);
		
		$success = false;
		try {
			$methodName = "add.me.up";
			$arguments = array(
				1,
				2,
				3
			);
			$x->checkArguments($methodName, $arguments);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);
		
		$success = false;
		try {
			$methodName = "add.me.up";
			$arguments = array(
				1
			);
			$x->checkArguments($methodName, $arguments);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);
		
		$success = false;
		try {
			$methodName = "add.me.up";
			$arguments = array(
				"A",
				"b"
			);
			$x->checkArguments($methodName, $arguments);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);
		
		$success = false;
		try {
			$methodName = "add.me.up";
			$arguments = array(
				"1",
				"2"
			);
			$x->checkArguments($methodName, $arguments);
		} catch (Exception $e) {
			$success = true;
		}
		$this->assert($success);
		
		$x->methodSignature();
		
		$x->getDocumentation();
		
		$x->methodHelp();
	}
}
