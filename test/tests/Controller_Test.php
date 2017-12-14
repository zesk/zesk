<?php
namespace zesk;

class Controller_Test extends Test_Unit {
	
	/**
	 * 
	 */
	function test_before_after() {
		$app = $this->application;
		$options = array();
		$testx = new Controller($app, $options);
		
		$testx->before();
		
		$testx->after();
	}
}
