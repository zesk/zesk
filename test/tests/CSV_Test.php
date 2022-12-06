<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * TODO Move to modules/csv/
 *
 * @author kent
 *
 */
class CSV_Test extends UnitTest {
	protected array $load_modules = [
		'csv',
	];

	/**
	 * @return \string[][]
	 */
	public function quote_data(): array {
		return [
			['foo', 'foo'],
			["fo\no", "\"fo\no\""],
			['fo"o', '"fo""o"'],
		];
	}

	/**
	 * @param string $item
	 * @param string $expected
	 * @return void
	 * @dataProvider quote_data
	 */
	public function test_quote(string $item, string $expected): void {
		$this->assertEquals($expected, CSV_Reader::quote($item));
	}

	public function test_quote_row(): void {
		$x = [
			'',
			'\'',
			'a long line with many spaces',
			'"Quotes"',
			'""',
		];
		$newx = CSV::quote_row($x);
		$this->assertEquals(',\',a long line with many spaces,"""Quotes""",""""""' . "\r\n", $newx);
	}
}
