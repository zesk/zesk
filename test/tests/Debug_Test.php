<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Debug_Test extends Test_Unit {
	function test_calling_file() {
		Debug::calling_file();
	}
	function test_calling_function() {
		$depth = 1;
		calling_function($depth);
	}
	function test_dump() {
		Debug::dump();
	}
	function test_output() {
		Debug::output();
	}
}

