<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use OutOfBoundsException;

/**
 *
 * @author kent
 *
 */
class Timestamp_Test extends UnitTest {
	public function data_instance(): array {
		return [['2022-11-16 10:32:32', 2022, 11, 16, 10, 32, 32], ['2000-01-01 09:09:02', 2000, 1, 1, 9, 9, 2], ];
	}

	/**
	 * @param $string_expected
	 * @param $year
	 * @param $month
	 * @param $day
	 * @param $hour
	 * @param $minute
	 * @param $second
	 * @return void
	 * @dataProvider data_instance
	 */
	public function test_instance($string_expected, $year, $month, $day, $hour, $minute, $second): void {
		$this->assertEquals($string_expected, Timestamp::instance($year, $month, $day, $hour, $minute, $second)->format());
	}

	public function test_utc(): void {
		$now = Timestamp::now('US/Eastern');
		$utc = Timestamp::utc_now();
		$this->assertNotEquals($utc->__toString(), $now->__toString());
	}

	public function test_now(): void {
		$now = Timestamp::now();
		$nowish = Timestamp::now();
		$this->assertTrue($nowish->after($now, true));
	}

	public function test_now_relative(): void {
		$Years = 0;
		$Months = 0;
		$Days = 0;
		$Hours = 0;
		$Minutes = 0;
		$Seconds = 0;
		$now = Timestamp::now();
		$other = clone $now;
		$now->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds);
		$this->assertEquals($now, $other);

		$now->add(0, 0, 0, 0, 0, 1);
		$this->assertNotEquals($now, $other);
	}

	public function data_seconds_to_unit(): array {
		return [
			[Temporal::UNIT_HOUR, 1.0, 3600, Temporal::UNIT_SECOND],
			[Temporal::UNIT_MINUTE, 1.0, 60, Temporal::UNIT_SECOND],
			[Temporal::UNIT_SECOND, 1.0, 1, Temporal::UNIT_SECOND],
		];
	}

	/**
	 * @param string $expected
	 * @param float $expectedFraction
	 * @param int $seconds
	 * @param string $stopAtUnit
	 * @return void
	 * @dataProvider data_seconds_to_unit
	 */
	public function test_seconds_to_unit(string $expected, float $expectedFraction, int $seconds, string $stopAtUnit): void {
		$fraction = null;
		$this->assertEquals($expected, Timestamp::secondsToUnit($seconds, $stopAtUnit, $fraction));
		$this->assertEquals($expectedFraction, $fraction);
	}

	public function units_translation_table(): void {
		$this->assertIsArray(Timestamp::unitsTranslationTable());
		$this->assertEquals(Timestamp::unitToSeconds('minute'), 60);
		$this->assertEquals(Timestamp::unitToSeconds('second'), 1);
	}

	public function units_translation_table_throw(): void {
		$this->expectException(Exception_Key::class);
		$this->assertEquals(Timestamp::unitToSeconds('nope'), 60);
	}

	/**
	 * @dataProvider good_months
	 */
	public function test_month_range($value): void {
		$x = new Timestamp();
		$x->setDay(1);
		$this->assertEquals($value, $x->setMonth($value)->month());
	}

	public function bad_months() {
		return [[-1], [0], [13], [14], [19], [141231241], [-12], [-11], ];
	}

	public function good_months() {
		return [[1], [2], [3], [4], [5], [6], [7], [8], [9], [10], [11], [12], ];
	}

	/**
	 * @dataProvider bad_months
	 */
	public function test_month_range_bad($month): void {
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setMonth($month);
	}

	/**
	 */
	public function test_month_range_high(): void {
		$x = new Timestamp();
		$value = null;
		$success = false;

		try {
			$x->setMonth(13);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$value = 0;
		$success = false;

		try {
			$x->setMonth($value);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$value = 13;
		$success = false;

		try {
			$x->setMonth($value);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$value = 11;
		$x->setMonth($value);
		$value = 12;
		$x->setMonth($value);
		$value = 1;
		$x->setMonth($value);
	}

	/**
	 */
	public function test_quarter_range_low(): void {
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setQuarter(0);
	}

	/**
	 */
	public function test_quarter_range_high(): void {
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setQuarter(5);
	}

	public function test_Timestamp(): void {
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

		$x->setNow();

		$Years = 0;
		$Months = 0;
		$Days = 0;
		$Hours = 0;
		$Minutes = 0;
		$Seconds = 0;
		Timestamp::now()->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds);

		$x->date();

		$x->time();

		$x->isEmpty();

		$x->setEmpty();

		$value = null;
		$x->set($value);

		$x->unixTimestamp();

		$ts = 1231204;
		$this->assertEquals($x, $x->setUnixTimestamp($ts));

		$x->__toString();

		$x->unixTimestamp();

		$success = false;
		$value = '';
		$locale_format = 'MDY;MD;MY;_';

		try {
			$x->parse_locale_string($value, $locale_format);
		} catch (Exception_Convert $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$success = true;
		$value = '10/2/1922';
		$locale_format = 'MDY;MD;MY;_';

		try {
			$x->parse_locale_string($value, $locale_format);
		} catch (\Exception $e) {
			$success = false;
		}
		$this->assertTrue($success);
		$this->assertEquals($x->setYear(2012)->year(), 2012);

		$this->assertEquals($x->setQuarter(3)->quarter(), 3);

		$x->month();

		$x->day();

		$x->weekday();

		$x->yearday();

		$x->hour();

		$x->minute();

		$x->second();

		$x->hour12();

		$x->ampm();

		$locale = $this->application->localeRegistry('en_US');
		$year = 2012;
		$month = 10;
		$day = 11;
		$x->ymd($year, $month, $day);
		$locale = $this->application->localeRegistry('en_US');
		$this->assertEquals($x->format($locale, '{YYYY}:{MM}:{DD}'), '2012:10:11');

		$value = 2011;
		$this->assertEquals($x->setYear($value), $x);
		$this->assertEquals($x->year(), $value);

		$success = false;

		try {
			$value = -1;
			$x->setDay($value);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$success = false;

		try {
			$value = 32;
			$x->setDay($value);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$success = true;

		try {
			$value = 30;
			$x->setMonth(2);
			$x->setDay($value);
		} catch (OutOfBoundsException $e) {
			$success = false;
		}
		// Lazy evaluation of
		$this->assertTrue($success);

		$success = false;

		try {
			$value = -1;
			$x->setDay($value);
		} catch (OutOfBoundsException $e) {
			$success = true;
		}
		$this->assertTrue($success, 'Day range exception failed');

		$value = 4;
		$x->weekday($value);

		for ($value = 1; $value <= 4; $value++) {
			$before = $x->format();
			$x->setQuarter($value);
			$this->assertEquals($value, $x->quarter(), "$before => " . $x->format());
		}

		$hour = 22;
		$minute = 23;
		$second = 24;
		$x->hms($hour, $minute, $second);
		$this->assertEquals($x->format($locale, '{hh}:{mm}:{ss}'), '22:23:24');
		$this->assertEquals($x->format($locale, '{12hh}:{mm}:{ss}'), '10:23:24');
		$this->assertEquals($x->format($locale, '{12hh}:{mm}:{ss} {ampm}'), '10:23:24 pm');
		$this->assertEquals($x->format($locale, '{12hh}:{mm}:{ss} {AMPM}'), '10:23:24 PM');
		$x->addUnit(-12, Timestamp::UNIT_HOUR);
		$this->assertEquals($x->format($locale, '{12hh}:{mm}:{ss} {AMPM}'), '10:23:24 AM');
		$this->assertEquals($x->format($locale, '{hh}:{mm}:{ss} {AMPM}'), '10:23:24 AM');
		$this->assertEquals($x->format($locale, '{hh}:{mm}:{ss} {ampm}'), '10:23:24 am');

		$year = 2011;
		$month = 12;
		$day = 31;
		$hour = 23;
		$minute = 59;
		$second = 59;
		$this->assertInstanceOf(Timestamp::class, $x->ymdhms($year, $month, $day, $hour, $minute, $second));

		for ($i = 0; $i < 24; $i++) {
			$x->setHour($value);
			$this->assertEquals($value, $x->hour());
		}
		for ($i = 0; $i < 59; $i++) {
			$x->setMinute($value);
			$this->assertEquals($value, $x->minute());
		}
		for ($i = 0; $i < 59; $i++) {
			$x->setSecond($value);
			$this->assertEquals($value, $x->second());
		}
		$value = new Timestamp('now');
		$this->assertGreaterThan($x->compare($value), 0);

		$tt = Timestamp::unitsTranslationTable();
		$this->assertArrayHasKeys([Temporal::UNIT_HOUR, Temporal::UNIT_SECOND, Temporal::UNIT_DAY], $tt);

		$seconds = 2;
		$divide = 0;
		$stopAtUnit = 'second';
		$this->assertEquals('second', Timestamp::secondsToUnit($seconds, $stopAtUnit, $divide));
		$this->assertEquals(2.0, $divide);

		$value = new Timestamp('2022-11-22 17:53:23');
		$x->subtract($value);

		$format_string = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}';
		$x->format($locale, $format_string);

		$model = new Timestamp('now');
		$this->assertTrue($x->before($model, false));

		$this->assertTrue($x->before($x, true));
		$this->assertFalse($x->before($x, false));

		$model = new Timestamp();
		$equal = false;
		$this->assertTrue($x->after($model, false));
		$this->assertTrue($x->after($model, true));

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

		$n = clone $x;
		$this->assertInstanceOf(Timestamp::class, $x->addUnit(1, 'second'));
		$this->assertTrue($x->after($n, true), $x->format() . ' After same object true is false?');
		$this->assertTrue($x->after($n, false));
		$this->assertTrue($n->before($x, true));
		$this->assertTrue($n->before($x, false));

		$x->iso8601();

		$value = null;
		$x->iso8601($value);

		date_default_timezone_set('UTC');

		// Test
		$long_date = 'Sat, 16-Aug-2064 04:11:10 GMT';

		$test_long_date = new Timestamp($long_date, null);
		$this->assertEquals($test_long_date->time()->format($locale), '04:11:10');
		$this->assertEquals($test_long_date->date()->format($locale), '2064-08-16');
		$threw = false;

		$ts = $test_long_date->unixTimestamp();
		$dt = new Timestamp();
		$dt->setUnixTimestamp($ts);
		$test_long_date_2 = new Timestamp();
		$test_long_date_2->setUnixTimestamp($ts);
		$this->assertEquals($test_long_date->__toString(), $test_long_date_2->__toString());
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_add_unit_deprecated(): void {
		$t = Timestamp::factory('2000-01-01 00:00:00', 'UTC');
		$t->addUnit(2, Timestamp::UNIT_HOUR);
	}

	public function test_difference(): void {
		$now = Timestamp::factory('now');
		$last_year = Timestamp::factory($now)->add(-1);
		$units = Timestamp::unitsTranslationTable();
		foreach ($units as $unit => $seconds) {
			$this->assertGreaterThan(0, $now->difference($last_year, $unit));
			$this->assertLessThan(0, $last_year->difference($now, $unit), "$unit should be negative");
		}

		$just_a_sec = Timestamp::factory($now)->addUnit(1, Timestamp::UNIT_SECOND);
		foreach ($units as $unit => $seconds) {
			$this->assertEquals($just_a_sec->difference($now, $unit), intval(round(1 / $seconds, 0)), $unit, false);
		}
	}

	/**
	 */
	public function test_parse_fail(): void {
		$this->expectException(Exception_Convert::class);
		$x = new Timestamp();
		$value = 'foo';
		$x->parse($value);
	}

	public function data_serializeExamples(): array {
		return [
			[Timestamp::now(), ], [Timestamp::factory_ymdhms(2000, 10, 2, 18, 59, 59, 123), ],
			[Timestamp::factory_ymdhms(1970, 12, 31, 18, 59, 59, 123), ],
			[Timestamp::factory_ymdhms(1950, 2, 14, 4, 0, 1, 412), ],
			[Timestamp::factory_ymdhms(1900, 2, 2, 23, 59, 59, 999), ],
		];
	}

	/**
	 * @dataProvider data_serializeExamples
	 * @param Timestamp $ts
	 */
	public function test_serialize(Timestamp $ts): void {
		$serialized = serialize($ts);
		$new = PHP::unserialize($serialized);
		$this->compare_timestamps($ts, $new);
	}

	public function compare_timestamps(Timestamp $ts, Timestamp $new): void {
		$this->assertEquals($ts->year(), $new->year(), 'years match');
		$this->assertEquals($ts->month(), $new->month(), 'months match');
		$this->assertEquals($ts->day(), $new->day(), 'days match');
		$this->assertEquals($ts->daySeconds(), $new->daySeconds(), 'day_seconds match');
		$this->assertEquals($ts->hour(), $new->hour(), 'hours match');
		$this->assertEquals($ts->minute(), $new->minute(), 'minutes match');
		$this->assertEquals($ts->second(), $new->second(), 'seconds match');
		$this->assertEquals($ts->millisecond(), $new->millisecond(), 'milliseconds match');
		$this->assertEquals($ts->format(), $new->format(), 'default format matches');
	}
}
