<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

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
		$option = "xmlrpc_test_url";
		$url = $this->option($option);
		if (!$url) {
			$this->markTestSkipped("Need to configure " . __CLASS__ . "::$option");
		}
		$this->url = $url;
	}
	function test_main() {
		$url = $this->url;
		$x = new \xmlrpc\Client($this->application, $url);
		
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
		
		$this->assert_is_array($x->response_header());
		
		$x->response_code();
		
		$x->response_code_type();
		
		$x->response_message();
		
		$x->response_protocol();
		
		$name = "Content-Length";
		$default = false;
		$x->response_header($name, $default);
		
		$x->go();
		
		$url = null;
		\xmlrpc\Client::simpleGet($url);
		
		$x->domain();
	}
}
