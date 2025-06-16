<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk;

use zesk\Exception\ClassNotFound;
use zesk\Number;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Number
 */
class NumberTestCase extends TestCase
{
	/**
	 * @return void
	 * @throws ClassNotFound
	 * @covers ::formatBytes
	 */
	public function test_format_bytes(): void
	{
		$locale = $this->application->localeRegistry('en');
		$tests = [
			0             => '0 bytes',
			1             => '1 byte',
			2             => '2 bytes',
			1024          => '1 KB',
			1024 * 1024   => '1 MB',
			1536133       => '1.5 MB',
			2684354560    => '2.5 GB',
			1099511627776 => '1 TB',
			2199023255552 => '2 TB',
		];

		foreach ($tests as $n => $result) {
			$this->assertEquals(Number::formatBytes($locale, $n), $result, "Number::format_bytes(\$locale, $n)");
		}
	}

	/**
	 * @param float  $expected
	 * @param string $bytes
	 * @return void
	 * @dataProvider data_parseBytes
	 * @covers ::parse_bytes
	 */
	public function test_parseBytes(float $expected, string $bytes): void
	{
		$this->assertEquals($expected, Number::parse_bytes($bytes));
	}

	public static function data_parseBytes(): array
	{
		return [
			[
				2199023255552.0,
				'2TB',
			],
			[
				2199023255552.0,
				'2T',
			],
			[
				2684354560.0,
				'2.5gb',
			],
			[
				2560.0,
				'2.5kb',
			],
			[
				2560.0,
				'2.5K',
			],
			[
				103809024.0,
				'99mb',
			],
			[
				103809024.0,
				'99m',
			],
		];
	}

	/**
	 * @param float $expected
	 * @param array $list
	 * @return void
	 * @dataProvider data_stddev
	 * @covers ::stddev
	 */
	public function test_stddev(float $expected, array $list): void
	{
		$this->assertEqualsWithDelta($expected, Number::stddev($list), 0.01);
		$this->assertEqualsWithDelta($expected, Number::stddev($list, Number::mean($list)), 0.01);
	}

	/**
	 *
	 */
	public static function data_stddev(): array
	{
		return [
			[
				0,
				[],
			],
			[
				1.0,
				[
					0.0,
					1.0,
					2.0,
				],
			],
			[
				0.1581,
				[
					0.1,
					0.2,
					0.3,
					0.4,
					0.5,
				],
			],
		];
	}

	/**
	 * @param float $expected
	 * @param array $list
	 * @return void
	 * @dataProvider data_mean
	 * @covers ::mean
	 */
	public function test_mean(float $expected, array $list): void
	{
		$this->assertEqualsWithDelta($expected, Number::mean($list), 0.01);
	}

	public static function data_mean(): array
	{
		return [
			[
				0,
				[],
			],
			[
				1.0,
				[
					0.0,
					1.0,
					2.0,
				],
			],
			[
				0.3,
				[
					0.1,
					0.2,
					0.3,
					0.4,
					0.5,
				],
			],
		];
	}

	/**
	 * @param $expected
	 * @param $minValue
	 * @param $value
	 * @param $maxValue
	 * @return void
	 * @dataProvider data_clamp
	 * @covers ::clamp
	 */
	public function test_clamp($expected, $minValue, $value, $maxValue): void
	{
		$this->assertEquals($expected, Number::clamp($minValue, $value, $maxValue));
	}

	public static function data_clamp(): array
	{
		return [
			[
				0,
				-1,
				0,
				1,
			],
			[
				-1,
				-1,
				-1,
				1,
			],
			[
				1,
				-1,
				1,
				1,
			],
			[
				1,
				-1,
				1.0000001,
				1,
			],
			[
				1,
				-1,
				1e99,
				1,
			],
			[
				-1,
				-1,
				-1e99,
				1,
			],
			[
				-1,
				-1,
				-1.0000001,
				1,
			],
			[
				-0.0000001,
				-1,
				-0.0000001,
				1,
			],
			[
				0.0000001,
				-1,
				0.0000001,
				1,
			],
		];
	}

	/**
	 * @return void
	 * @covers ::intBetween
	 */
	public function test_integer_between(): void
	{
		$this->assertTrue(Number::intBetween(10, 10, 200));
		$this->assertFalse(Number::intBetween(10, 9, 200));
		$this->assertTrue(Number::intBetween(10, 200, 200));
		$this->assertFalse(Number::intBetween(10, 201, 200));
	}
}
