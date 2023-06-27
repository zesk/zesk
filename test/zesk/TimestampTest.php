<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use OutOfBoundsException;
use zesk\Exception\ParseException;

/**
 *
 * @author kent
 *
 */
class TimestampTest extends UnitTest
{
	public static function data_instance(): array
	{
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
	public function test_instance($string_expected, $year, $month, $day, $hour, $minute, $second): void
	{
		$this->assertEquals($string_expected, Timestamp::instance($year, $month, $day, $hour, $minute, $second)->format());
	}

	public function test_utc(): void
	{
		$now = Timestamp::now('US/Eastern');
		$utc = Timestamp::nowUTC();
		$this->assertNotEquals($utc->__toString(), $now->__toString());
	}

	public function test_now(): void
	{
		$now = Timestamp::now();
		$nowish = Timestamp::now();
		$this->assertTrue($nowish->after($now, true));
	}

	public function test_now_relative(): void
	{
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

	public static function data_seconds_to_unit(): array
	{
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
	public function test_seconds_to_unit(string $expected, float $expectedFraction, int $seconds, string $stopAtUnit): void
	{
		$fraction = null;
		$this->assertEquals($expected, Timestamp::secondsToUnit($seconds, $stopAtUnit, $fraction));
		$this->assertEquals($expectedFraction, $fraction);
	}

	public function units_translation_table(): void
	{
		$this->assertIsArray(Timestamp::unitsTranslationTable());
		$this->assertEquals(60, Timestamp::unitToSeconds('minute'));
		$this->assertEquals(1, Timestamp::unitToSeconds('second'));
	}

	public function units_translation_table_throw(): void
	{
		$this->expectException(Exception\KeyNotFound::class);
		$this->assertEquals(60, Timestamp::unitToSeconds('nope'));
	}

	/**
	 * @dataProvider data_good_months
	 */
	public function test_month_range($value): void
	{
		$x = new Timestamp();
		$x->setDay(1);
		$this->assertEquals($value, $x->setMonth($value)->month());
	}

	public static function data_bad_months(): array
	{
		return [[-1], [0], [13], [14], [19], [141231241], [-12], [-11], ];
	}

	public static function data_good_months(): array
	{
		return [[1], [2], [3], [4], [5], [6], [7], [8], [9], [10], [11], [12], ];
	}

	/**
	 * @dataProvider data_bad_months
	 */
	public function test_month_range_bad($month): void
	{
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setMonth($month);
	}

	/**
	 */
	public function test_month_range_high(): void
	{
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
	public function test_quarter_range_low(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setQuarter(0);
	}

	/**
	 */
	public function test_quarter_range_high(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$x = new Timestamp();
		$x->setQuarter(5);
	}

	public function test_Timestamp(): void
	{
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

		$ts = 1231204;
		$this->assertEquals($x, $x->setUnixTimestamp($ts));
		$this->assertEquals($ts, $x->unixTimestamp());

		$x->__toString();

		$x->unixTimestamp();

		$success = false;
		$value = '';
		$locale_format = 'MDY;MD;MY;_';

		try {
			$x->parseLocaleString($value, $locale_format);
		} catch (ParseException $e) {
			$success = true;
		}
		$this->assertTrue($success);

		$success = true;
		$value = '10/2/1922';
		$locale_format = 'MDY;MD;MY;_';

		try {
			$x->parseLocaleString($value, $locale_format);
		} catch (\Exception $e) {
			$success = false;
		}
		$this->assertTrue($success);
		$this->assertEquals(2012, $x->setYear(2012)->year());

		$this->assertEquals(3, $x->setQuarter(3)->quarter());

		$x->month();

		$x->day();

		$x->weekday();

		$x->yearday();

		$x->hour();

		$x->minute();

		$x->second();

		$x->hour12();

		$x->ampm();

		$year = 2012;
		$month = 10;
		$day = 11;
		$x->setYMD($year, $month, $day);
		$locale = $this->application->localeRegistry('en_US');
		$this->assertEquals('2012:10:11', $x->format('{YYYY}:{MM}:{DD}'));

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
		$x->setWeekday($value);

		for ($value = 1; $value <= 4; $value++) {
			$before = $x->format();
			$x->setQuarter($value);
			$this->assertEquals($value, $x->quarter(), "$before => " . $x->format());
		}

		$hour = 22;
		$minute = 23;
		$second = 24;
		$x->setHMS($hour, $minute, $second);
		$args = ['locale' => $locale];
		$this->assertEquals('22:23:24', $x->format('{hh}:{mm}:{ss}'));
		$this->assertEquals('10:23:24', $x->format('{12hh}:{mm}:{ss}'));
		$this->assertEquals('10:23:24 {ampm}', $x->format('{12hh}:{mm}:{ss} {ampm}'));
		$this->assertEquals('10:23:24 pm', $x->format('{12hh}:{mm}:{ss} {ampm}', $args));
		$this->assertEquals('10:23:24 PM', $x->format('{12hh}:{mm}:{ss} {AMPM}', $args));
		$x->addUnit(-12, Temporal::UNIT_HOUR);
		$this->assertEquals('10:23:24 {AMPM}', $x->format('{12hh}:{mm}:{ss} {AMPM}'));
		$this->assertEquals('10:23:24 AM', $x->format('{12hh}:{mm}:{ss} {AMPM}', $args));
		$this->assertEquals('10:23:24 AM', $x->format('{hh}:{mm}:{ss} {AMPM}', $args));
		$this->assertEquals('10:23:24 am', $x->format('{hh}:{mm}:{ss} {ampm}', $args));

		$year = 2011;
		$month = 12;
		$day = 31;
		$hour = 23;
		$minute = 59;
		$second = 59;
		$this->assertInstanceOf(Timestamp::class, $x->setYMDHMS($year, $month, $day, $hour, $minute, $second));

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
		$value = Timestamp::now();
		$this->assertGreaterThan($x->compare($value), 0);

		$tt = Timestamp::unitsTranslationTable();
		$this->assertArrayHasKeys([Temporal::UNIT_HOUR, Temporal::UNIT_SECOND, Temporal::UNIT_DAY], $tt);

		$seconds = 2;
		$divide = 0;
		$stopAtUnit = 'second';
		$this->assertEquals('second', Timestamp::secondsToUnit($seconds, $stopAtUnit, $divide));
		$this->assertEquals(2.0, $divide);

		$value = Timestamp::factory()->parse('2022-11-22 17:53:23');
		$x->subtract($value);

		$format_string = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}';
		$x->format($format_string);

		$model = Timestamp::now();
		$this->assertTrue($x->before($model, false));

		$this->assertTrue($x->before($x, true));
		$this->assertFalse($x->before($x, false));

		$model = new Timestamp();
		$model2 = Timestamp::now();
		$ts2=$model2->unixTimestamp();
		$this->assertEquals($model->unixTimestamp(), $ts2);

		$equal = false;
		$this->assertFalse($x->after($model, true));
		$this->assertFalse($x->after($model, $equal));

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

		$value = '1970-01-01T00:00:00';
		$x->setISO8601($value);

		date_default_timezone_set('UTC');

		// Test
		$long_date = 'Sat, 16-Aug-2064 04:11:10 GMT';

		$test_long_date = (new Timestamp())->parse($long_date);
		$this->assertEquals('04:11:10', $test_long_date->time()->format());
		$this->assertEquals('2064-08-16', $test_long_date->date()->format());
		$threw = false;

		$ts = $test_long_date->unixTimestamp();
		$dt = new Timestamp();
		$dt->setUnixTimestamp($ts);
		$test_long_date_2 = new Timestamp();
		$test_long_date_2->setUnixTimestamp($ts);
		$this->assertEquals($test_long_date->__toString(), $test_long_date_2->__toString());
	}

	public function test_difference(): void
	{
		$now = Timestamp::now();
		$last_year = Timestamp::factory($now)->add(-1);
		$units = Timestamp::unitsTranslationTable();
		foreach ($units as $unit => $seconds) {
			$this->assertGreaterThan(0, $now->difference($last_year, $unit));
			$this->assertLessThan(0, $last_year->difference($now, $unit), "$unit should be negative");
		}

		$just_a_sec = Timestamp::factory($now)->addUnit(1, Temporal::UNIT_SECOND);
		foreach ($units as $unit => $seconds) {
			$this->assertEquals($just_a_sec->difference($now, $unit), intval(round(1 / $seconds, 0)), $unit);
		}
	}

	/**
	 */
	public function test_parse_fail(): void
	{
		$this->expectException(ParseException::class);
		$x = new Timestamp();
		$value = 'foo';
		$x->parse($value);
	}

	public static function data_serializeExamples(): array
	{
		return [
			[Timestamp::now(), ], [Timestamp::instance(2000, 10, 2, 18, 59, 59, 123), ],
			[Timestamp::instance(1970, 12, 31, 18, 59, 59, 123), ], [Timestamp::instance(1950, 2, 14, 4, 0, 1, 412), ],
			[Timestamp::instance(1900, 2, 2, 23, 59, 59, 999), ],
		];
	}

	/**
	 * @dataProvider data_serializeExamples
	 * @param Timestamp $ts
	 */
	public function test_serialize(Timestamp $ts): void
	{
		$serialized = serialize($ts);
		$new = PHP::unserialize($serialized);
		$this->compare_timestamps($ts, $new);
	}

	public function compare_timestamps(Timestamp $ts, Timestamp $new): void
	{
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
