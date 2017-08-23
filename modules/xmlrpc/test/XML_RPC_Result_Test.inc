<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017its, Market Acumen, Inc.
 */
namespace zesk;

use xmlrpc\Result;

class XML_RPC_Result_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	function test_basics() {
		$x = new Result();
		
		$x->isFault();
		
		$x->faultError();
	}
}
