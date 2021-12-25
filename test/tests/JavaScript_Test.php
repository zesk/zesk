<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class JavaScript_Test extends Test_Unit {
	public function test_clean_function_name(): void {
		$x = null;
		JavaScript::clean_function_name($x);
	}

	public function test_null(): void {
		$x = null;
		JavaScript::null($x);
	}

	public function test_obfuscate_begin(): void {
		JavaScript::obfuscate_begin();
		JavaScript::obfuscate_end([]);
	}

	/**
	 * @expectedException zesk\Exception_Semantics
	 */
	public function test_obfuscate_begin2(): void {
		JavaScript::obfuscate_begin();
		JavaScript::obfuscate_begin();
	}

	/**
	 * @depends test_obfuscate_begin2
	 * @expectedException zesk\Exception_Semantics
	 */
	public function test_obfuscate_end(): void {
		$function_map = [];
		JavaScript::obfuscate_end($function_map);
		JavaScript::obfuscate_end($function_map);
	}

	public function test_string(): void {
		$x = null;
		JavaScript::string($x);
	}
}
