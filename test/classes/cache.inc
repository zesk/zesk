<?php

use zesk\Cache;

class test_cache extends Test_Unit {

	function test_register() {
		$name = 'test';
		$x = Cache::register($name);

		$x->cache_file_path();

		$x->dump();

		$x->flush();

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
