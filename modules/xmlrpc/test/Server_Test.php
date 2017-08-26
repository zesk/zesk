<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Test_Unit;
use zesk\str;

class MyServer extends Server {
	function rpc_capitalize($string) {
		return str::capitalize($string);
	}
}
/**
 * 
 * @author kent
 *
 */
class Server_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	function test_basics() {
		$methods = false;
		$x = new MyServer($methods);
		
		$x->registerMethod("capitalize", "string", "this:capitalize", array(
			"string" => "string"
		), "Capitalizes a word", array(
			"string" => "String to capitalize"
		));
		
		$methodName = "dude";
		$args = null;
		$x->call($methodName, $args);
		
		$error = null;
		$message = false;
		$x->error($error, $message);
		
		$xml = null;
		$x->response($xml);
		
		$method = null;
		$x->hasMethod($method);
		
		$arr = null;
		$x->registerMethods($arr);
		
		$name = null;
		$returnType = null;
		$phpMethod = null;
		$parameterTypes = false;
		$help = false;
		$parameterHelp = false;
		$x->registerMethod($name, $returnType, $phpMethod, $parameterTypes, $help, $parameterHelp);
		
		$name = null;
		$specURL = null;
		$specVersion = 1;
		$x->addCapability($name, $specURL, $specVersion);
		
		$x->rpc_getCapabilities();
		
		$x->rpc_listMethods();
		
		$methodCalls = null;
		$x->rpc_multicall($methodCalls);
		
		$method = null;
		$x->rpc_methodSignature($method);
		
		$method = null;
		$x->rpc_methodHelp($method);
	}
	
	/**
	 * @expectedException xmlrpc\Exception
	 */
	function test_missing_method() {
		$methods = false;
		$x = new MyServer($methods);
		
		// 		$data = false;
		// 		$x->serve($data);
		
		$methodName = "missing";
		$args = null;
		$x->call($methodName, $args);
	}
}
