<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Server_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL"
	);
	protected function initialize() {
		$this->schema_synchronize("zesk\\Server");
	}
	function test_Server() {
		$this->application->configuration->HOST = "localhost";
		
		$mixed = null;
		$options = false;
		$testx = new Server($this->application, $mixed, $options);
		
		$testx = Server::singleton($this->application);
		
		$this->assert_instanceof($testx, __NAMESPACE__ . "\\" . "Server");
		$path = "/";
		$testx->id = 1;
		$testx->update_state($path);
	}
}
