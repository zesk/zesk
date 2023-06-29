<?php
declare(strict_types=1);
/**
 *
 */

namespace test;

use zesk\PHPUnit\TestCase;
use zesk\StringTools;

/**
 * TODO Move to modules/csv/
 *
 * @author kent
 *
 */
class CSVTest extends TestCase
{
	protected array $load_modules = [
		'CSV',
	];

	/**
	 * @return array
	 */
	public static function data_quote(): array
	{
		return [
			['foo', 'foo'], ["fo\no", "\"fo\no\""], ['fo"o', '"fo""o"'],
		];
	}

	/**
	 * @param string $item
	 * @param string $expected
	 * @return void
	 * @dataProvider data_quote
	 */
	public function test_quote(string $item, string $expected): void
	{
		$this->assertEquals($expected, StringTools::csvQuote($item));
		$this->assertEquals("$expected\r\n", StringTools::csvQuoteRow([$item]));
		$this->assertEquals("$expected\r\n$expected\r\n", StringTools::csvQuoteRows([[$item], [$item]]));
	}

	public static function data_csvQuoteRow(): array
	{
		return [
			[
				',\',a long line with many spaces,"""Quotes""",""""""' . "\r\n", [
					'', '\'', 'a long line with many spaces', '"Quotes"', '""',
				],
			],
		];
	}

	/**
	 * @param string $expected
	 * @param array $row
	 * @return void
	 * @dataProvider data_csvQuoteRow
	 */
	public function test_csvQuoteRow(string $expected, array $row): void
	{
		$this->assertEquals($expected, StringTools::csvQuoteRow($row));
	}
}
