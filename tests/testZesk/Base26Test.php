<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Base26;

/**
 * @coversDefaultClass \zesk\Base26
 * @uses Base26
 */
class Base26Test extends TestCase
{
	/**
	 * @return array[]
	 */
	public static function data_fromInteger(): array
	{
		return [
			['AAAAAC', 2, 6],
			['A', 0, 1],
			['AA', 0, 2],
			['AAAAA', 0, 5],
			['AAAAB', 1, 5],
			['KENTY', 4649370, 1],
			['KENTY', 4649370, 5],
		];
	}

	/**
	 * @param $expected
	 * @param int $test
	 * @param int $nCharacters
	 * @covers ::fromInteger
	 * @return void
	 * @dataProvider data_fromInteger
	 */
	public function test_from_integer($expected, int $test, int $nCharacters): void
	{
		$this->assertEquals($expected, Base26::fromInteger($test, $nCharacters));
	}

	/**
	 * @return array[]
	 */
	public static function data_toInteger(): array
	{
		return [
			[0, 'A'],
			[0, 'AA'],
			[0, 'AAAAAAAAAAAA'],
			[1, 'AAAAB'],
			[4649370, 'KENTY'],
			[4649370, 'KENTY'],
		];
	}

	/**
	 * @dataProvider data_toInteger
	 * @param int $expected
	 * @param string $test
	 * @return void
	 * @covers ::toInteger
	 */
	public function test_to_integer(int $expected, string $test): void
	{
		$this->assertEquals($expected, Base26::toInteger($test));
	}
}
