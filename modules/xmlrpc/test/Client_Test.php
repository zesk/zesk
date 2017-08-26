<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Test_Unit;
use zesk\Exception_Unsupported;

class Client_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	protected $url = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Test_Unit::initialize()
	 */
	function initialize() {
		$url = $this->option("xmlrpc_test_url");
		if (!$url) {
			throw new Exception_Unsupported("Need to configure {class}::xmlrpc_test_url", array(
				"class" => __CLASS__
			));
		}
		$this->url = $url;
	}
	function test_main() {
		$url = $this->url;
		$x = new Client($url);
		
		$x->setCallMap(array(
			"__" => "."
		));
		
		//$x->set_option("debug", true);
		$x->query("system.listmethods");
		
		$x->system__listmethods();
		
		$x->isFault();
		
		$default = false;
		$x->user_agent();
		
		$value = "Dude";
		$x->user_agent($value);
		
		$x->isFault();
		
		$x->url();
		
		$x->headers();
		
		$x->response_code();
		
		$x->response_code_type();
		
		$x->response_message();
		
		$x->response_protocol();
		
		$name = "Content-Length";
		$default = false;
		$x->response_header($name, $default);
		
		$x->go();
		
		$url = null;
		Client::simpleGet($url);
		
		$x->domain();
	}
}
