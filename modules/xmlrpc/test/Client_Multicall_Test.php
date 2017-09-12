<?php
namespace zesk;

use xmlrpc\Client_Multicall;

class Client_Multicall_Test extends Test_Unit {
	protected $load_modules = array(
		"xmlrpc"
	);
	/**
	 * 
	 * @var unknown
	 */
	private $url = null;
	/**
	 * 
	 */
	function _init() {
		$option = "xmlrpc_test_url";
		$this->url = $this->option($option);
		if (!URL::valid($this->url)) {
			$this->markTestIncomplete("No configuration " . __CLASS__ . "::$option");
		}
	}
	
	/**
	 * @no_buffer
	 */
	function test_xmlrpc() {
		$this->_init();
		
		$x = new Client_Multicall($this->application, $this->url);
		$x->set_option('debug', true);
		
		$x->addCall("system.listmethods");
		$x->addCall("capitalize", "a sentence needing capitalization");
		$x->addCall("unknown_function", "will fault");
		
		$result = $x->query();
		
		$this->assert(is_array($result));
		$this->assert(count($result) === 3);
		
		$list_result = $result[0];
		$this->assert_in_array($list_result, "capitalize");
		$this->assert_in_array($list_result, "system.getcapabilities");
		$this->assert_in_array($list_result, "system.listmethods");
		$this->assert_in_array($list_result, "system.multicall");
		
		$cap_result = $result[1];
		$this->assert_equal($cap_result, "A Sentence Needing Capitalization");
		
		$fault_result = $result[2];
		$this->assert_equal($fault_result, array(
			"faultCode" => -32601,
			"faultString" => "Server error. Requested method \"unknown_function\" does not exist."
		));
		$this->log($result);
		
		$x->clear();
		
		$x->isFault();
		
		$x->url();
		
		$x->response_code();
		
		$x->response_code_type();
		
		$x->response_message();
		
		$x->response_protocol();
		
		$this->log("Content-Type is " . $x->response_header("Content-Type"));
	}
}
