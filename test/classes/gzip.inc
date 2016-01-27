<?php
class test_gzip extends Test_Unit {

	function test_enabled() {
		$set = null;
		gzip::enabled($set);
	}

	function test_end() {
		gzip::end();
	}

	function test_ok() {
		gzip::ok();
	}

	function test_start() {
		gzip::start();
	}
}
