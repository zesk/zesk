<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\SemanticsException;
use zesk\JavaScript;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\JavaScript
 * @uses JavaScript
 */
class JavaScriptTest extends TestCase
{
	public static function dataCleanFunctionName(): array
	{
		return [
			[
				'foobar',
				'foo bar',
			],
		];
	}

	public static function dataNull(): array
	{
		return [
			['null', null],
			['null', 'null'],
			['nullish', 'nullish'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $name
	 * @return void
	 * @dataProvider  dataCleanFunctionName
	 * @covers ::cleanFunctionName
	 */
	public function testCleanFunctionName(string $expected, string $name): void
	{
		$this->assertEquals($expected, JavaScript::cleanFunctionName($name));
	}

	/**
	 * @param string $expected
	 * @param mixed $test
	 * @return void
	 * @covers ::null
	 * @dataProvider dataNull
	 */
	public function testNull(string $expected, mixed $test): void
	{
		$this->assertEquals($expected, JavaScript::null($test));
	}

	public static function dataObfuscate(): array
	{
		return [
			['function (){return true ;}()', 'function () { return true ; }()'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $code
	 * @return void
	 * @covers ::obfuscateBegin
	 * @covers ::obfuscateEnd
	 * @dataProvider dataObfuscate
	 */
	public function testObfuscate(string $expected, string $code): void
	{
		$this->assertEquals($expected, JavaScript::obfuscate($code));
	}

	public static function dataString(): array
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
	 * @dataProvider dataString
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @covers ::string
	 */
	public function testString(string $expected, string $test): void
	{
		$this->assertEquals($expected, JavaScript::string($test));
	}
}
