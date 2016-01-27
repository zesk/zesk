<?php
class test_js extends Test_Unit {

	function test_clean_function_name() {
		$x = null;
		js::clean_function_name($x);
	}

	function test_null() {
		$x = null;
		js::null($x);
	}

	function test_obfuscate_begin() {
		js::obfuscate_begin();
		js::obfuscate_end(array());
	}

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_obfuscate_begin2() {
		js::obfuscate_begin();
		js::obfuscate_begin();
	}

	/**
	 * @expected_exception Exception_Semantics
	 */
	function test_obfuscate_end() {
		$function_map = array();
		js::obfuscate_end($function_map);
		js::obfuscate_end($function_map);
	}

	function test_string() {
		$x = null;
		js::string($x);
	}
}
