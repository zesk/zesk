<?php
class Session_PHP_Test extends Test_Unit {

	function test_main() {
		$testx = Session_PHP::instance(true);
		
		$testx->id();
		
		$name = "Foo";
		$default = null;
		$testx->get($name, $default);
		
		$name = "Foo";
		$testx->__get($name);
		
		$name = "Foo";
		$value = null;
		$testx->__set($name, $value);
		
		$list = null;
		$testx->filter($list);
		
		$testx->user_id();
		
		$id = null;
		$ip = false;
		$testx->authenticate($id, $ip);
		
		$testx->deauthenticate();
		
		$testx->delete();
		
		echo basename(__FILE__) . ": success\n";
	}
}
