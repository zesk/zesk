<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/date.inc $
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
		$locale = null;
		$short = false;
		Date::weekday_names($locale, $short);
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
	 * @expected_exception zesk\Exception_Range
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
		$locale = null;
		$short = false;
		Date::month_names($locale, $short);
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

		$format_string = '{YYYY}-{MM}-{DD}';
		$x->format($format_string);
	}
}

