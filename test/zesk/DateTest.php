<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use OutOfBoundsException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;

/**
 *
 * @author kent
 *
 */
class DateTest extends UnitTest {
	public static function data_daysInMonth(): array {
		return [
			[1, 2022, 31], [2, 2022, 28], [3, 2022, 31], [4, 2022, 30], [5, 2022, 31], [6, 2022, 30], [7, 2022, 31],
			[8, 2022, 31], [9, 2022, 30], [10, 2022, 31], [11, 2022, 30], [12, 2022, 31],
		];
	}

	/**
	 * @param int $month
	 * @param int $year
	 * @param int $expected
	 * @return void
	 * @dataProvider data_daysInMonth
	 */
	public function test_daysInMonth(int $month, int $year, int $expected): void {
		$this->assertEquals($expected, Date::daysInMonth($month, $year), "Date::daysInMonth($month, $year)");
	}

	public static function data_yearDay(): array {
		return [
			['1970-01-01', '1970-01-01', 0, 0], ['1970-01-02', '1970-01-01', 0, 1], ['1970-01-03', '1970-01-01', 0, 2],
			['1970-01-04', '1970-01-01', 0, 3], ['1970-02-01', '1970-01-01', 0, 31],
			['1970-02-02', '1970-01-01', 0, 32], ['1970-12-31', '1970-01-01', 0, 364],
			['1971-01-01', '1970-01-01', 0, 365],
		];
	}

	/**
	 * @param string|Date $expected
	 * @param string|Date $start
	 * @param int $isYearday
	 * @param int $setYearday
	 * @return void
	 * @throws ParseException
	 * @dataProvider data_yearDay
	 */
	public function test_yearDay(string|Date $expected, string|Date $start, int $isYearday, int $setYearday): void {
		if (is_string($start)) {
			$start = (new Date())->parse($start);
		}
		$this->assertInstanceOf(Date::class, $start);
		$this->assertEquals($isYearday, $start->yearday());
		$result = $start->setYearday($setYearday);
		if (is_string($expected)) {
			$expected = (new Date())->parse($expected);
		}
		$this->assertInstanceOf(Date::class, $expected);
		$this->assertInstanceOf(Date::class, $result);
		$this->assertEquals(strval($expected), strval($result));
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 */
	public function test_emptyYearDay(): void {
		$date = new Date();
		$this->assertNull($date->yearday());
		$this->expectException(SemanticsException::class);
		$date->setYearday(12);
	}

	public function test_emptyYearDay2(): void {
		$date = new Date();
		$date->setNow();
		$this->assertNotNull($date->yearday());
		$date->setEmpty();
		$this->assertNull($date->yearday());
		$this->expectException(SemanticsException::class);
		$date->setYearday(12);
	}

	public function test_weekday_names(): void {
		$locale = $this->application->locale;
		$names = Date::weekdayNames($locale, false);
		$this->assertCount(7, $names);
	}

	public static function data_DateRangeFail(): array {
		return [
			[
				-1, null, null,
			], [
				null, -1, null,
			], [
				null, 0, null,
			], [
				null, 13, null,
			], [
				null, null, -1,
			], [
				null, null, 0,
			], [
				null, null, 32,
			],
		];
	}

	/**
	 * @dataProvider data_DateRangeFail
	 * @param $y
	 * @param $m
	 * @param $d
	 * @return void
	 */
	public function test_range_fail($y, $m, $d): void {
		$this->expectException(OutOfBoundsException::class);
		Date::instance($y, $m, $d);
	}

	public function optDate(): void {
		$d = new Date();
		$value = '2022-01-03';
		$d->parse($value);
		$d->set($value);
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
		$result = Date::monthNames($locale, $short);
		$this->assertCount(12, $result);
	}

	public function test_now(): void {
		Date::now();
	}

	public function test_Date(): void {
		$x = new Date();
		$this->assertInstanceOf(Date::class, $x);

		$y = Date::instance();
		$this->assertInstanceOf(Date::class, $y);

		$now = Date::now();
		$this->assertInstanceOf(Date::class, $now);

		$x->parse('2008-08-20');
		$this->assertEquals(2008, $x->year());
		$this->assertEquals(8, $x->month());
		$this->assertEquals(20, $x->day());

		foreach ([
			'{YYYY}-{MM}-{DD}' => '2008-08-20', '{YY}-{MM}-{DD}' => '08-08-20', '{YY}-{M}-{D}' => '08-8-20',
			'{MMM} {DDD}, {YYYY}' => 'Aug 20th, 2008', '{MMMM} {DDD}, {YYYY}' => 'August 20th, 2008',
			'{YYY} {DDDD}' => '{YYY} {DDDD}',
		] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($format_string, ['locale' => $this->application->locale]));
		}

		$x->parse('1999-12-01');
		foreach ([
			'{YYYY}-{MM}-{DD}' => '1999-12-01', '{YY}-{MM}-{DD}' => '99-12-01', '{YY}-{M}-{D}' => '99-12-1',
			'{MMM} {DDD}, {YYYY}' => 'Dec 1st, 1999', '{MMMM} {DDD}, {YYYY}' => 'December 1st, 1999',
			'{YYY} {DDDD}' => '{YYY} {DDDD}',
		] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($format_string, ['locale' => $this->application->locale]));
		}
	}
}
