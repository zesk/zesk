<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Server_Test extends Test_Unit {
	protected $load_modules = array(
		"server"
	);
	protected function initialize() {
		$this->schema_synchronize("zesk\\Server");
	}
	function test_Server() {
		$this->application->configuration->HOST = "localhost";
		
		$mixed = null;
		$options = false;
		$testx = new Server($mixed, $options);
		
		$testx = Server::singleton(__CLASS__);
		
		$path = "/";
		$testx->ID = 1;
		$testx->update_state($path);
	}
}
