<?php
namespace zesk;

class Cache_Test extends Test_Unit {
	function test_register() {
		$name = 'test';
		$x = $this->application->cache->getItem(__CLASS__);

		$x->
		$k = null;
		$x->__get($k);
		
		$k = null;
		$v = null;
		$x->__set($k, $v);
		
		$name = "test-cache";
		Cache::register($name);
	}
	function test_at_exit() {
		Cache::at_exit();
	}
}
