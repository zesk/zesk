<?php
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
	function test_days_in_month() {
		$month = null;
		$year = null;
		Date::days_in_month($month, $year);
	}
	function test_weekday_names() {
		$locale = $this->application->locale;
		$short = false;
		$names = Date::weekday_names($locale, $short);
		$this->assertEquals(7, count($names));
	}
	function _test_range_fail_parameters() {
		return array(
			array(
				-1,
				null,
				null
			),
			array(
				null,
				-1,
				null
			),
			array(
				null,
				0,
				null
			),
			array(
				null,
				13,
				null
			),
			array(
				null,
				null,
				-1
			),
			array(
				null,
				null,
				0
			),
			array(
				null,
				null,
				32
			)
		);
	}
	
	/**
	 * @expectedException zesk\Exception_Range
	 * @data_provider _test_range_fail_parameters
	 */
	function test_range_fail($y, $m, $d) {
		Date::instance($y, $m, $d);
	}
	function test_foo() {
		$year = null;
		$month = null;
		$day = null;
		Date::instance($year, $month, $day);
	}
	function test_month_names() {
		$locale = $this->application->locale;
		$short = false;
		$result = Date::month_names($locale, $short);
		$this->assertEquals(12, count($result));
	}
	function test_now() {
		Date::now();
	}
	function test_Date() {
		$value = null;
		$options = false;
		$x = new Date($value, $options);
		
		Date::instance();
		
		Date::now();
		
		$x->parse("2008-08-20");
		$this->assert($x->year() === 2008);
		$this->assert($x->month() === 8);
		$this->assert($x->day() === 20);
		
		foreach (array(
			'{YYYY}-{MM}-{DD}' => "2008-08-20",
			'{YY}-{MM}-{DD}' => "08-08-20",
			'{YY}-{M}-{D}' => "08-8-20",
			'{YY}-{MM}-{DD}' => "08-08-20",
			'{MMM} {DDD}, {YYYY}' => "Aug 20th, 2008",
			'{MMMM} {DDD}, {YYYY}' => "August 20th, 2008",
			'{YYY} {DDDD}' => "{YYY} {DDDD}"
		) as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}
		
		$x->parse("1999-12-01");
		foreach (array(
			'{YYYY}-{MM}-{DD}' => "1999-12-01",
			'{YY}-{MM}-{DD}' => "99-12-01",
			'{YY}-{M}-{D}' => "99-12-1",
			'{YY}-{MM}-{DD}' => "99-12-01",
			'{MMM} {DDD}, {YYYY}' => "Dec 1st, 1999",
			'{MMMM} {DDD}, {YYYY}' => "December 1st, 1999",
			'{YYY} {DDDD}' => "{YYY} {DDDD}"
		) as $format_string => $expected) {
			$this->assertEquals($expected, $x->format($this->application->locale, $format_string));
		}
	}
}

