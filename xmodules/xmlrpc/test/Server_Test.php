<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class XMLRPC_Server_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	function initialize() {
		parent::initialize();
		require_once __DIR__ . '/MyServer.php';
	}
	function test_basics() {
		$methods = false;
		$x = new MyServer($this->application);
		
		$x->registerMethod("capitalize", "string", "this:capitalize", array(
			"string" => "string"
		), "Capitalizes a word", array(
			"string" => "String to capitalize"
		));
		
		$methodName = "capitalize";
		$args = array(
			"hello, world, how are you"
		);
		$result = $x->call($methodName, $args);
		$this->assert_equal($result, "Hello, World, How Are You");
		
		$method = null;
		$x->hasMethod($method);
		
		$arr = array();
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
		
		$methodCalls = array();
		$x->rpc_multicall($methodCalls);
		
		$method = null;
		$x->rpc_methodSignature($method);
		
		$method = null;
		$x->rpc_methodHelp($method);
	}
	/**
	 * @todo Fix this so it doesn't exit and use zesk\Request
	 *
	 */
	function __test_error() {
		$x = new MyServer($this->application);
		
		$error = null;
		$message = false;
		$x->error($error, $message);
	}
	/**
	 * @todo Fix this so it doesn't exit and use zesk\Request
	 * 
	 */
	function __test_response() {
		$x = new MyServer($this->application);
		
		$xml = null;
		$x->response($xml);
	}
	
	/**
	 * @expectedException xmlrpc\Exception
	 */
	function test_missing_method() {
		$x = new MyServer($this->application);
		
		// 		$data = false;
		// 		$x->serve($data);
		
		$methodName = "missing";
		$args = null;
		$x->call($methodName, $args);
	}
	
	/**
	 * @expectedException xmlrpc\Exception
	 */
	function test_wrong_method() {
		$x = new MyServer($this->application);
		
		$x->registerMethod("capitalize", "string", "this:capitalize", array(
			"string" => "string"
		), "Capitalizes a word", array(
			"string" => "String to capitalize"
		));
		
		$methodName = "dude";
		$args = null;
		$x->call($methodName, $args);
	}
}
