<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk;

use zesk\Exception\ProtocolException;
use zesk\JavaScript;

/**
 *
 * @author kent
 *
 */
class JavaScriptTestCase extends TestCase {
	
	public static function data_cleanFunctionName(): array
	{
		return [
			[
				'foobar',
				'foo bar',
			],
		];
	}
	
	/**
	 * @return void
	 * @dataProvider  data_cleanFunctionName
	 */
	public function test_clean_function_name($expected, $name): void
	{
		$this->assertEquals($expected, JavaScript::clean_function_name($name));
	}
	
	public function test_null(): void
	{
		$this->assertEquals('null', JavaScript::null(null));
		$this->assertEquals('null', JavaScript::null('null'));
		$this->assertEquals('nullish', JavaScript::null('nullish'));
	}
	
	public function test_obfuscate_begin(): void
	{
		JavaScript::obfuscate_begin();
		JavaScript::obfuscate_end([]);
	}
	
	public function test_obfuscate_begin2(): void
	{
		JavaScript::obfuscate_begin();
		JavaScript::obfuscate_end();
	}
	
	/**
	 */
	public function test_obfuscate_end(): void
	{
		$this->expectException(ProtocolException::class);
		$function_map = [];
		JavaScript::obfuscate_end($function_map);
		JavaScript::obfuscate_end($function_map);
	}
	
	public static function data_string(): array
	{
		return [
			[
				'\'Normal string\'',
				'Normal string',
			],
			[
				"'That\'s string'",
				'That\'s string',
			],
			[
				"'Multiline\\n' +\n'String'",
				"Multiline\nString",
			],
		];
	}
	
	/**
	 * @dataProvider data_string
	 * @return void
	 */
	public function test_string($expected, $test): void
	{
		$this->assertEquals($expected, JavaScript::string($test));
	}
}
