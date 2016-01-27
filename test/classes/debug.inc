<?php
class test_debug extends Test_Unit {

	function test_calling_file() {
		debug::calling_file();
	}

	function test_calling_function() {
		$depth = 1;
		debug::calling_function($depth);
	}

	function test_dump() {
		debug::dump();
	}

	function test_output() {
		debug::output();
	}
}

