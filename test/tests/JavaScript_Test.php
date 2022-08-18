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
class JavaScript_Test extends UnitTest {
	public function data_cleanFunctionName(): array {
		return [
			['foobar', 'foo bar'],
		];
	}

	/**
	 * @return void
	 * @dataProvider  data_cleanFunctionName
	 */
	public function test_clean_function_name($expected, $name): void {
		$this->assertEquals($expected, JavaScript::clean_function_name($name));
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

	public function data_string(): array {
		return [
			['\'Normal string\'', 'Normal string'],
			["'That\'s string'", 'That\'s string'],
			["'Multiline\\n' +\n'String'", "Multiline\nString"],
		];
	}

	/**
	 * @dataProvider data_string
	 * @return void
	 */
	public function test_string($expected, $test): void {
		$this->assertEquals($expected, JavaScript::string($test));
	}
}
