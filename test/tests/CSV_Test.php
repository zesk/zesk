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
class Test_CSV extends Test_Unit {
	protected array $load_modules = [
		"csv",
	];

	public function test_quote(): void {
		$x = null;
		CSV_Reader::quote($x);
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
		dump($newx);
		$this->assert($newx === ',\',a long line with many spaces,"""Quotes""",""""""' . "\r\n");
	}
}
