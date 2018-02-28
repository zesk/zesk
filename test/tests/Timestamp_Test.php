<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Timestamp_Test extends Test_Unit {
	function test_instance() {
		$year = $month = $day = $hour = $minute = $second = null;
		Timestamp::instance($year, $month, $day, $hour, $minute, $second);
	}
	function test_utc() {
		$now = Timestamp::now();
		$utc = Timestamp::utc_now();
		$this->assert_not_equal($utc->__toString(), $now->__toString());
	}
	function test_now() {
		Timestamp::now();
	}
	function test_now_relative() {
		$Years = 0;
		$Months = 0;
		$Days = 0;
		$Hours = 0;
		$Minutes = 0;
		$Seconds = 0;
		$now = Timestamp::now();
		$other = clone $now;
		$now->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds);
		$this->assert_equal($now, $other);

		$now->add(0, 0, 0, 0, 0, 1);
		$this->assert_not_equal($now, $other);
	}
	function test_seconds_to_unit() {
		$seconds = null;
		$divide = false;
		$stopAtUnit = 'second';
		Timestamp::seconds_to_unit($seconds, $divide, $stopAtUnit);
	}
	function units_translation_table() {
		$this->assert(is_array(Timestamp::units_translation_table()));
		$this->assert_equal(Timestamp::units_translation_table('minute'), 60);
		$this->assert_equal(Timestamp::units_translation_table('second'), 1);
	}

	/**
	 * @dataProvider good_months
	 */
	function test_month_range($value) {
		$x = new Timestamp();
		$x->month($value);
	}
	function bad_months() {
		return array(
			-1,
			0,
			13,
			14,
			19,
			141231241,
			-12,
			-11
		);
	}
	function good_months() {
		return array(
			1,
			2,
			3,
			4,
			5,
			6,
			7,
			8,
			9,
			10,
			11,
			12
		);
	}

	/**
	 * @dataProvider bad_months
	 * @expectedException zesk\Exception_Range
	 */
	function test_month_range_bad($month) {
		$x = new Timestamp();
		$x->month($month);
	}

	/**
	 */
	function test_month_range_high() {
		$x = new Timestamp();
		$value = null;
		$success = false;
		try {
			$x->month(13);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert($success);

		$value = 0;
		$success = false;
		try {
			$x->month($value);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert($success);

		$value = 13;
		$success = false;
		try {
			$x->month($value);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert($success);

		$value = 11;
		$x->month($value);
		$value = 12;
		$x->month($value);
		$value = 1;
		$x->month($value);
	}

	/**
	 * @expectedException zesk\Exception_Range
	 */
	function test_quarter_range_low() {
		$x = new Timestamp();
		$x->quarter(0);
	}

	/**
	 * @expectedException zesk\Exception_Range
	 */
	function test_quarter_range_high() {
		$x = new Timestamp();
		$x->quarter(5);
	}
	function test_Timestamp() {
		$value = null;
		$options = null;
		$x = new Timestamp($value, $options);

		$year = null;
		$month = null;
		$day = null;
		$hour = null;
		$minute = null;
		$second = null;
		Timestamp::instance($year, $month, $day, $hour, $minute, $second);
		$year = 1999;
		Timestamp::instance($year, $month, $day, $hour, $minute, $second);

		Timestamp::now();

		$x->set_now();

		$Years = 0;
		$Months = 0;
		$Days = 0;
		$Hours = 0;
		$Minutes = 0;
		$Seconds = 0;
		Timestamp::now()->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds);

		$x->date();

		$x->time();

		$x->is_empty();

		$x->set_empty();

		$value = null;
		$x->set($value);

		$x->unix_timestamp();

		$ts = 1231204;
		$this->assert($x->unix_timestamp($ts) === $x);

		$x->__toString();

		$x->unix_timestamp();

		$success = false;
		$value = null;
		$locale_format = 'MDY;MD;MY;_';
		try {
			$x->parse_locale_string($value, $locale_format);
		} catch (Exception_Convert $e) {
			$success = true;
		}
		$this->assert($success);

		$success = true;
		$value = "10/2/1922";
		$locale_format = 'MDY;MD;MY;_';
		try {
			$x->parse_locale_string($value, $locale_format);
		} catch (Exception $e) {
			$success = false;
		}
		$this->assert($success);
		$this->assert_equal($x->year("2012")->year(), 2012);

		$this->assert_equal($x->quarter("3")->quarter(), 3);

		$x->month();

		$x->day();

		$x->weekday();

		$x->yearday();

		$x->hour();

		$x->minute();

		$x->second();

		$x->hour12();

		$x->ampm();

		$locale = $this->application->locale_registry("en_US");
		$year = 2012;
		$month = 10;
		$day = 11;
		$x->ymd($year, $month, $day);
		$locale = $this->application->locale_registry("en_US");
		$this->assert_equal($x->format($locale, "{YYYY}:{MM}:{DD}"), "2012:10:11");

		$value = 2011;
		$this->assert_equal($x->year($value), $x);
		$this->assert_equal($x->year(null), $value);
		$this->assert_equal($x->year(), $value);

		$success = false;
		try {
			$value = -1;
			$x->day($value);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert($success);

		$success = false;
		try {
			$value = 32;
			$x->day($value);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert($success);

		$success = true;
		try {
			$value = 30;
			$x->month(2);
			$x->day($value);
		} catch (Exception_Range $e) {
			$success = false;
		}
		// Lazy evaluation of
		$this->assert($success);

		$success = false;
		try {
			$value = -1;
			$x->day($value);
		} catch (Exception_Range $e) {
			$success = true;
		}
		$this->assert_true($success, "Day range exception failed");

		$value = 4;
		$x->weekday($value);

		$value = 3;
		$x->quarter($value);

		$hour = 22;
		$minute = 23;
		$second = 24;
		$x->hms($hour, $minute, $second);
		$this->assert_equal($x->format($locale, "{hh}:{mm}:{ss}"), "22:23:24");
		$this->assert_equal($x->format($locale, "{12hh}:{mm}:{ss}"), "10:23:24");
		$this->assert_equal($x->format($locale, "{12hh}:{mm}:{ss} {ampm}"), "10:23:24 pm");
		$this->assert_equal($x->format($locale, "{12hh}:{mm}:{ss} {AMPM}"), "10:23:24 PM");
		$x->add_unit(-12, Timestamp::UNIT_HOUR);
		$this->assert_equal($x->format($locale, "{12hh}:{mm}:{ss} {AMPM}"), "10:23:24 AM");
		$this->assert_equal($x->format($locale, "{hh}:{mm}:{ss} {AMPM}"), "10:23:24 AM");
		$this->assert_equal($x->format($locale, "{hh}:{mm}:{ss} {ampm}"), "10:23:24 am");

		$year = 2011;
		$month = 12;
		$day = 31;
		$hour = 23;
		$minute = 59;
		$second = 59;
		$x->ymdhms($year, $month, $day, $hour, $minute, $second);

		$value = null;
		$x->hour($value);

		$value = null;
		$x->minute($value);

		$value = null;
		$x->second($value);

		$value = new Timestamp();
		$x->compare($value);

		$flip = false;
		Timestamp::units_translation_table();

		$seconds = null;
		$divide = false;
		$stopAtUnit = 'second';
		Timestamp::seconds_to_unit($seconds, $divide, $stopAtUnit);

		$value = new Timestamp();
		$x->subtract($value);

		$format_string = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}';
		$x->format($locale, $format_string);

		$model = new Timestamp();
		$equal = false;
		$x->before($model, $equal);

		$model = new Timestamp();
		$equal = false;
		$x->after($model, $equal);

		$model = new Timestamp();
		$x->later($model);

		$model = new Timestamp();
		$x->earlier($model);

		$Years = 0;
		$Months = 0;
		$Days = 0;
		$Hours = 0;
		$Minutes = 0;
		$Seconds = 0;
		$x->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds);

		$endTime = new Timestamp();
		$unit = 'second';
		$precision = 0;
		$x->difference($endTime, $unit, $precision);

		$endTime = new Timestamp();
		$unit = 'second';
		$precision = 2;
		$x->difference($endTime, $unit, $precision);

		$unit = 'second';
		$n = 1;
		$x->add_unit($n, $unit);

		$x->iso8601();

		$value = null;
		$x->iso8601($value);

		date_default_timezone_set('UTC');

		// Test
		$long_date = "Sat, 16-Aug-2064 04:11:10 GMT";

		$test_long_date = new Timestamp($long_date, null);
		$this->assert_equal($test_long_date->time()->format($locale), "04:11:10");
		$this->assert_equal($test_long_date->date()->format($locale), "2064-08-16");
		$threw = false;
		try {
			echo $test_long_date->format($locale) . "\n";
			$ts = $test_long_date->unix_timestamp();
			$dt = new Timestamp();
			$dt->unix_timestamp($ts);
		} catch (Exception_Convert $e) {
			$threw = true;
		}
		$should_throw = PHP_VERSION_ID < 50209;
		if (php_uname('s') === "Darwin") {
			$should_throw = PHP_VERSION_ID <= 50400 ? true : false;
		}
		if ($should_throw) {
			$this->assert($threw, "Exception_Convert was not thrown? PHP_VERSION_ID=" . PHP_VERSION_ID);
		} else {
			$this->assert(!$threw, "Exception_Convert was thrown? PHP_VERSION_ID=" . PHP_VERSION_ID);
			$test_long_date_2 = new Timestamp();
			$test_long_date_2->unix_timestamp($ts);
			$this->assert($test_long_date_2->__toString() === $test_long_date->__toString(), $test_long_date_2->__toString() . " === " . $test_long_date->__toString());
		}
	}
	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	function test_add_unit_deprecated() {
		$t = Timestamp::factory('2000-01-01 00:00:00', 'UTC');
		$t->add_unit(Timestamp::UNIT_HOUR, 2);
	}
	function test_difference() {
		$now = Timestamp::factory('now');
		$last_year = Timestamp::factory($now)->add(-1);
		$units = Timestamp::units_translation_table();
		foreach ($units as $unit => $seconds) {
			$this->assert_positive($now->difference($last_year, $unit), "$unit should be positive");
			$this->assert_negative($last_year->difference($now, $unit), "$unit should be negative");
		}

		$just_a_sec = Timestamp::factory($now)->add_unit(1, Timestamp::UNIT_SECOND);
		foreach ($units as $unit => $seconds) {
			$this->assert_equal($just_a_sec->difference($now, $unit), intval(round(1 / $seconds, 0)), $unit, false);
		}
	}
	/**
	 * @expectedException zesk\Exception_Convert
	 */
	function test_parse_fail() {
		$x = new Timestamp();
		$value = null;
		$x->parse($value);
	}
}
