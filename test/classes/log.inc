<?php
class log_test extends Test_Unit {

	function test_level() {
		$level = null;
		log::level($level);
	}
	function test_send() {
		$x = "hello, {hello}";
		$args = array(
			"hello" => "world"
		);
		log::error($x, $args);
	}
	function test_rotate() {
		$path = null;
		$size_limit = 10485760;
		$keep_count = 7;
		log::rotate($path, $size_limit, $keep_count);
	}
}
