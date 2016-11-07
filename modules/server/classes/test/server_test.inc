<?php
class Server_Test extends Test_Unit {
	protected function initialize() {
		Module::load('server');
		$this->schema_synchronize("Server");
	}
	function test_Server() {
		zesk::set('HOST', 'localhost');
		
		$mixed = null;
		$options = false;
		$testx = new Server($mixed, $options);
		
		$testx = Server::singleton(__CLASS__);
		
		$path = "/";
		$testx->ID = 1;
		$testx->update_state($path);
	}
}
