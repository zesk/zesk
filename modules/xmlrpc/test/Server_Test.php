<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/XML_RPC_Server.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Test_Unit;

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
		$x = new Server($methods);
		
		$data = false;
		$x->serve($data);
		
		$methodName = null;
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
}
