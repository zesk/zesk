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
class Number_Test extends UnitTest {
	public function test_format_bytes(): void {
		$locale = $this->application->localeRegistry('en');
		$tests = [
			0 => '0 bytes',
			1 => '1 byte',
			2 => '2 bytes',
			1024 => '1 KB',
			1024 * 1024 => '1 MB',
			1536133 => '1.5 MB',
			2684354560 => '2.5 GB',
			1099511627776 => '1 TB',
			2199023255552 => '2 TB',
		];

		foreach ($tests as $n => $result) {
			$this->assertEquals(Number::formatBytes($locale, $n), $result, "Number::format_bytes(\$locale, $n)");
		}
	}

	public function data_parseBytes(): array {
		return [
			[2199023255552.0, '2TB'],
			[2199023255552.0, '2T'],
			[2684354560.0, '2.5gb'],
			[2560.0, '2.5kb'],
			[2560.0, '2.5K'],
			[103809024.0, '99mb'],
			[103809024.0, '99m'],
		];
	}

	/**
	 * @param float $expected
	 * @param string $bytes
	 * @return void
	 * @dataProvider data_parseBytes
	 */
	public function test_parseBytes(float $expected, string $bytes): void {
		$this->assertEquals($expected, Number::parse_bytes($bytes));
	}

	/**
	 *
	 */
	public function data_stddev(): array {
		return [
			[0, []],
			[1.0, [0.0, 1.0, 2.0]],
			[0.1581, [0.1, 0.2, 0.3, 0.4, 0.5]],
		];
	}

	/**
	 *
	 */
	/**
	 * @param float $expected
	 * @param array $list
	 * @return void
	 * @dataProvider data_stddev
	 */
	public function test_stddev(float $expected, array $list): void {
		$this->assertEqualsWithDelta($expected, Number::stddev($list), 0.01);
		$this->assertEqualsWithDelta($expected, Number::stddev($list, Number::mean($list)), 0.01);
	}

	/**
	 * @param float $expected
	 * @param array $list
	 * @return void
	 * @dataProvider data_mean
	 */
	public function test_mean(float $expected, array $list): void {
		$this->assertEqualsWithDelta($expected, Number::mean($list), 0.01);
	}

	public function data_mean(): array {
		return [
			[0, []],
			[1.0, [0.0, 1.0, 2.0]],
			[0.3, [0.1, 0.2, 0.3, 0.4, 0.5]],
		];
	}
}
