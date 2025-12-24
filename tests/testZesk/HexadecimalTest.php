<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Hexadecimal;

/**
 *
 * @author kent
 * @uses   Hexadecimal
 * @coversDefaultClass Hexadecimal
 */
class HexadecimalTest extends TestCase
{
	public static function data_decode(): array
	{
		return [
			[
				[
					chr(222),
					chr(173),
					chr(190),
					chr(239),
				],
				'DEADBEEF',
			],
		];
	}

	/**
	 * @return void
	 * @covers ::decode
	 * @dataProvider data_decode
	 */
	public function test_decode(array $values, string $test): void
	{
		$this->assertEquals($values, str_split(Hexadecimal::decode($test)));
	}

	/**
	 * Reversing the arguments is arguably probably better but whatever
	 *
	 * @return array
	 */
	public static function data_encode(): array
	{
		$testArguments = [];
		foreach (self::data_decode() as $test) {
			$testArguments[] = [
				$test[1],
				$test[0],
			];
		}
		return $testArguments;
	}

	/**
	 * @param array $test
	 * @param string $expected
	 * @return void
	 * @covers ::encode
	 * @dataProvider data_encode
	 * @return void
	 */
	public function test_encode(string $expected, array $test): void
	{
		$this->assertEquals($expected, Hexadecimal::encode(implode('', $test)));
	}
}
