<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Date_Test extends UnitTest {
	public function daysInMonthData(): array {
		return [
			[1, 2022, 31],
			[2, 2022, 28],
			[3, 2022, 31],
			[4, 2022, 30],
			[5, 2022, 31],
			[6, 2022, 30],
			[7, 2022, 31],
			[8, 2022, 31],
			[9, 2022, 30],
			[10, 2022, 31],
			[11, 2022, 30],
			[12, 2022, 31],
		];
	}

	/**
	 * @param int $month
	 * @param int $year
	 * @param int $expected
	 * @return void
	 * @throws Exception_Range
	 * @dataProvider daysInMonthData
	 */
	public function test_daysInMonth(int $month, int $year, int $expected): void {
		$this->assertEquals($expected, Date::daysInMonth($month, $year), "Date::daysInMonth($month, $year)");
	}

	public function test_weekday_names(): void {
		$locale = $this->application->locale;
		$names = Date::weekday_names($locale, false);
		$this->assertCount(7, $names);
	}

	public function _test_range_fail_parameters(): array {
		return [
			[
				-1,
				null,
				null,
			],
			[
				null,
				-1,
				null,
			],
			[
				null,
				0,
				null,
			],
			[
				null,
				13,
				null,
			],
			[
				null,
				null,
				-1,
			],
			[
				null,
				null,
				0,
			],
			[
				null,
				null,
				32,
			],
		];
	}

	/**
	 * @dataProvider _test_range_fail_parameters
	 * @param $y
	 * @param $m
	 * @param $d
	 * @return void
	 */
	public function test_range_fail($y, $m, $d): void {
		$this->expectException(Exception_Range::class);
		Date::instance($y, $m, $d);
	}

	public function optDate(): void {
		$d = new Date();
		$value = '2022-01-03';
		$d->set($value);
		$d?->set($value);
	}

	public function test_foo(): void {
		$year = null;
		$month = null;
		$day = null;
		Date::instance($year, $month, $day);
	}

	public function test_month_names(): void {
		$locale = $this->application->locale;
		$short = false;
		$result = Date::month_names($locale, $short);
		$this->assertEquals(12, count($result));
	}

	public function test_now(): void {
		Date::now();
	}

	public function test_Date(): void {
		$value = null;
		$x = new Date($value);
		$this->assertInstanceOf(Date::class, $x);

		$y = Date::instance();
		$this->assertInstanceOf(Date::class, $y);

		$now = Date::now();
		$this->assertInstanceOf(Date::class, $now);

		$x->parse('2008-08-20');
		$this->assert($x->year() === 2008);
		$this->assert($x->month() === 8);
		$this->assert($x->day() === 20);

		foreach ([
					 '{YYYY}-{MM}-{DD}' => '2008-08-20',
					 '{YY}-{MM}-{DD}' => '08-08-20',
					 '{YY}-{M}-{D}' => '08-8-20',
					 '{MMM} {DDD}, {YYYY}' => 'Aug 20th, 2008',
					 '{MMMM} {DDD}, {YYYY}' => 'August 20th, 2008',
					 '{YYY} {DDDD}' => '{YYY} {DDDD}',
				 ] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}

		$x->parse('1999-12-01');
		foreach ([
					 '{YYYY}-{MM}-{DD}' => '1999-12-01',
					 '{YY}-{MM}-{DD}' => '99-12-01',
					 '{YY}-{M}-{D}' => '99-12-1',
					 '{MMM} {DDD}, {YYYY}' => 'Dec 1st, 1999',
					 '{MMMM} {DDD}, {YYYY}' => 'December 1st, 1999',
					 '{YYY} {DDDD}' => '{YYY} {DDDD}',
				 ] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}
	}
}
