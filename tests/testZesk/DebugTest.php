<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Debug;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Debug
 * @uses Debug
 */
class DebugTest extends TestCase
{
	/**
	 * @return void
	 * @covers ::callingFile
	 */
	public function testCallingFile(): void
	{
		$this->assertEquals(__FILE__, Debug::callingFile());
	}

	public static function dataOutput(): array
	{
		return self::dataDump();
	}

	public static function dataDump(): array
	{
		return [
			['(null)', null],
			['(boolean) true', true],
			['(boolean) false', false],
			['array()', []],
		];
	}

	/**
	 * @param string $expected
	 * @param mixed $dump
	 * @return void
	 * @covers ::dump
	 * @dataProvider dataDump
	 */
	public function testDump(string $expected, mixed $dump): void
	{
		$this->assertEquals($expected, Debug::dump($dump));
	}

	/**
	 * @param string $expected
	 * @param mixed $dump
	 * @return void
	 * @covers ::output
	 * @dataProvider dataOutput
	 */
	public function testOutput(string $expected, mixed $dump): void
	{
		ob_start();
		Debug::output($dump);
		$dumped = ob_get_clean();
		$this->assertEquals("$expected\n", $dumped);
	}
}
