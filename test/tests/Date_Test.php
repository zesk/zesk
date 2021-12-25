<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Test_Date extends Test_Unit {
	public function test_days_in_month(): void {
		$month = null;
		$year = null;
		Date::days_in_month($month, $year);
	}

	public function test_weekday_names(): void {
		$locale = $this->application->locale;
		$short = false;
		$names = Date::weekday_names($locale, $short);
		$this->assertEquals(7, count($names));
	}

	public function _test_range_fail_parameters() {
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
	 * @expectedException zesk\Exception_Range
	 * @data_provider _test_range_fail_parameters
	 */
	public function test_range_fail($y, $m, $d): void {
		Date::instance($y, $m, $d);
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
		$options = false;
		$x = new Date($value, $options);

		Date::instance();

		Date::now();

		$x->parse("2008-08-20");
		$this->assert($x->year() === 2008);
		$this->assert($x->month() === 8);
		$this->assert($x->day() === 20);

		foreach ([
			'{YYYY}-{MM}-{DD}' => "2008-08-20",
			'{YY}-{MM}-{DD}' => "08-08-20",
			'{YY}-{M}-{D}' => "08-8-20",
			'{YY}-{MM}-{DD}' => "08-08-20",
			'{MMM} {DDD}, {YYYY}' => "Aug 20th, 2008",
			'{MMMM} {DDD}, {YYYY}' => "August 20th, 2008",
			'{YYY} {DDDD}' => "{YYY} {DDDD}",
		] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}

		$x->parse("1999-12-01");
		foreach ([
			'{YYYY}-{MM}-{DD}' => "1999-12-01",
			'{YY}-{MM}-{DD}' => "99-12-01",
			'{YY}-{M}-{D}' => "99-12-1",
			'{YY}-{MM}-{DD}' => "99-12-01",
			'{MMM} {DDD}, {YYYY}' => "Dec 1st, 1999",
			'{MMMM} {DDD}, {YYYY}' => "December 1st, 1999",
			'{YYY} {DDDD}' => "{YYY} {DDDD}",
		] as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}
	}
}
