<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use OutOfBoundsException;
use zesk\Date;
use zesk\Exception\ClassNotFound;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParseException;
use zesk\Exception\SyntaxException;
use zesk\PHP;
use zesk\Temporal;
use zesk\Timestamp;

/**
 *
 * @author kent
 * @uses   Timestamp
 * @coversDefaultClass Timestamp
 */
class TimestampTest extends TestCase
{
    public static function data_instance(): array
    {
        return [
            [
                '2022-11-16 10:32:32',
                2022,
                11,
                16,
                10,
                32,
                32,
            ],
            [
                '2000-01-01 09:09:02',
                2000,
                1,
                1,
                9,
                9,
                2,
            ],
        ];
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
     * @covers ::instance
     */
    public function test_instance($string_expected, $year, $month, $day, $hour, $minute, $second): void
    {
        $this->assertEquals($string_expected, Timestamp::instance($year, $month, $day, $hour, $minute, $second)->format());
    }

    /**
     * @return void
     * @covers ::nowUTC
     * @covers ::now
     */
    public function test_utc(): void
    {
        $now = Timestamp::now('US/Eastern');
        $utc = Timestamp::nowUTC();
        $this->assertNotEquals($utc->__toString(), $now->__toString());
    }

    /**
     * @return void
     * @covers ::now ::after
     */
    public function test_now(): void
    {
        $now = Timestamp::now();
        $nowish = Timestamp::now();
        $this->assertTrue($nowish->after($now, true));
    }

    /**
     * @return void
     * @covers ::add ::now
     */
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
        $this->assertEquals($now->format(Timestamp::DEFAULT_FORMAT_STRING), $other->format(Timestamp::DEFAULT_FORMAT_STRING));
        $this->assertEquals(strval($now), strval($other));

        $now->add(0, 0, 0, 0, 0, 1);
        $this->assertEquals($now->format(Date::DEFAULT_FORMAT_STRING), $other->format(Date::DEFAULT_FORMAT_STRING));
        $this->assertNotEquals(strval($now), strval($other));
    }

    public static function data_seconds_to_unit(): array
    {
        return [
            [
                Temporal::UNIT_HOUR,
                1.0,
                3600,
                Temporal::UNIT_SECOND,
            ],
            [
                Temporal::UNIT_MINUTE,
                1.0,
                60,
                Temporal::UNIT_SECOND,
            ],
            [
                Temporal::UNIT_SECOND,
                1.0,
                1,
                Temporal::UNIT_SECOND,
            ],
        ];
    }

    /**
     * @param string $expected
     * @param float $expectedFraction
     * @param int $seconds
     * @param string $stopAtUnit
     * @return void
     * @dataProvider data_seconds_to_unit
     * @covers ::secondsToUnit
     */
    public function test_seconds_to_unit(string $expected, float $expectedFraction, int $seconds, string $stopAtUnit): void
    {
        $fraction = null;
        $this->assertEquals($expected, Timestamp::secondsToUnit($seconds, $stopAtUnit, $fraction));
        $this->assertEquals($expectedFraction, $fraction);
    }

    /**
     * @return void
     * @throws KeyNotFound
     * @covers ::unitsTranslationTable
     * @covers ::unitToSeconds
     */
    public function test_unitsTranslationTable(): void
    {
        $this->assertIsArray(Timestamp::unitsTranslationTable());
        $this->assertEquals(60, Timestamp::unitToSeconds('minute'));
        $this->assertEquals(1, Timestamp::unitToSeconds('second'));
    }

    /**
     * @throws KeyNotFound
     * @covers ::unitToSeconds
     */
    public function test_unitsTranslationTableThrow(): void
    {
        $this->expectException(KeyNotFound::class);
        $this->assertEquals(60, Timestamp::unitToSeconds('nope'));
    }

    /**
     * @dataProvider data_good_months
     * @covers ::setDay ::setMonth ::month
     */
    public function test_month_range($value): void
    {
        $x = new Timestamp();
        $x->setDay(1);
        $this->assertEquals($value, $x->setMonth($value)->month());
    }

    public static function data_bad_months(): array
    {
        return [
            [-1],
            [0],
            [13],
            [14],
            [19],
            [141231241],
            [-12],
            [-11],
        ];
    }

    public static function data_good_months(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [5],
            [6],
            [7],
            [8],
            [9],
            [10],
            [11],
            [12],
        ];
    }

    /**
     * @dataProvider data_bad_months
     * @covers ::setMonth
     */
    public function test_month_range_bad($month): void
    {
        $this->expectException(OutOfBoundsException::class);
        $x = new Timestamp();
        $x->setMonth($month);
    }

    /**
     * @covers ::setMonth
     */
    public function test_month_range_high(): void
    {
        $x = new Timestamp();
        $success = false;

        try {
            $x->setMonth(13);
        } catch (OutOfBoundsException) {
            $success = true;
        }
        $this->assertTrue($success);

        $value = 0;
        $success = false;

        try {
            $x->setMonth($value);
        } catch (OutOfBoundsException) {
            $success = true;
        }
        $this->assertTrue($success);

        $value = 13;
        $success = false;

        try {
            $x->setMonth($value);
        } catch (OutOfBoundsException) {
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
     * @covers ::setQuarter
     */
    public function test_quarter_range_low(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $x = new Timestamp();
        $x->setQuarter(0);
    }

    /**
     * @covers ::setQuarter
     */
    public function test_quarter_range_high(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $x = new Timestamp();
        $x->setQuarter(5);
    }

    /**
     * @return void
     * @covers ::add
     */
    public function test_add(): void
    {
        $x = Timestamp::now();
        $old = $x->duplicate()->addUnit(-1);

        $this->assertTrue($x->setNow()->after($old), 'setNow is after');

        $p = $x->duplicate();
        $Years = 0;
        $Months = 0;
        $Days = 0;
        $Hours = 0;
        $Minutes = 0;
        $Seconds = 0;

        $this->assertEquals($p->add($Years, $Months, $Days, $Hours, $Minutes, $Seconds)->unixTimestamp(), $x->unixTimestamp());
    }

    /**
     * @return void
     * @covers ::date ::year ::month ::day
     */
    public function test_date(): void
    {
        $x = Timestamp::now();

        $date = $x->date();
        $this->assertEquals($date->year(), $x->year());
        $this->assertEquals($date->month(), $x->month());
        $this->assertEquals($date->day(), $x->day());
    }

    /**
     * @return void
     * @covers ::time ::hour ::minute ::second ::millisecond
     */
    public function test_time(): void
    {
        $x = Timestamp::now();

        $time = $x->time();
        $this->assertEquals($time->hour(), $x->hour());
        $this->assertEquals($time->minute(), $x->minute());
        $this->assertEquals($time->second(), $x->second());
        $this->assertEquals($time->millisecond(), $x->millisecond());
    }

    /**
     * @return void
     * @covers ::factory ::setUnixTimestamp ::unixTimestamp
     */
    public function test_unixTimestamp(): void
    {
        $x = Timestamp::factory();

        $ts = 1231204;
        $this->assertEquals($x, $x->setUnixTimestamp($ts));
        $this->assertEquals($ts, $x->unixTimestamp());
    }

    public static function data_parseLocaleStringParseError(): array
    {
        return [
            [
                '',
                'MDY;MD;MY;_',
            ],
        ];
    }

    public static function data_parseLocaleStringParse(): array
    {
        return [
            [
                '10/2/1922',
                'MDY;MD;MY;_',
            ],
        ];
    }

    /**
     * @param string $value
     * @param string $locale_format
     * @return void
     * @covers ::parseLocaleString
     * @dataProvider  data_parseLocaleStringParseError
     * @return void
     * @throws ParseException
     */
    public function test_parseLocaleStringError(string $value, string $locale_format): void
    {
        $x = Timestamp::factory();
        $this->expectException(ParseException::class);
        $this->assertFalse($x->parseLocaleString($value, $locale_format));
    }

    /**
     * @param string $value
     * @param string $locale_format
     * @return void
     * @covers ::parseLocaleString
     * @dataProvider  data_parseLocaleStringParse
     * @return void
     * @throws ParseException
     */
    public function test_parseLocaleString(string $value, string $locale_format): void
    {
        $x = Timestamp::factory();
        $this->assertTrue($x->parseLocaleString($value, $locale_format), 'value parsed successfully');
    }

    public static function data_year(): array
    {
        return [
            [1929],
            [1939],
            [1970],
            [2000],
            [2012],
            [2012],
            [2012],
            [2035],
            [2060],
            [2050],
            [2100],
        ];
    }

    /**
     * @param int $year
     * @return void
     * @covers ::setYear ::year
     * @dataProvider data_year
     */
    public function test_setYearSuccess(int $year): void
    {
        $x = Timestamp::factory();
        $this->assertEquals($year, $x->setYear($year)->year());
    }

    public static function data_quarter(): array
    {
        return [
            [1],
            [2],
            [3],
            [4],
        ];
    }

    /**
     * @param int $quarter
     * @return void
     * @dataProvider data_quarter
     * @covers ::quarter ::setQuarter
     */
    public function test_quarter(int $quarter): void
    {
        $x = Timestamp::factory();
        $this->assertEquals($quarter, $x->setQuarter($quarter)->quarter());
    }

    /**
     * @throws ParseException
     */
    public static function data_valueRanges(): array
    {
        $timestamps = [];
        $t = Timestamp::factory()->parse('2000-01-01 00:00:00');
        $timestamps[] = [$t->unixTimestamp()];
        foreach ([
                     Temporal::UNIT_SECOND,
                     Temporal::UNIT_MINUTE,
                     Temporal::UNIT_HOUR,
                     Temporal::UNIT_DAY,
                     Temporal::UNIT_MONTH,
                     Temporal::UNIT_YEAR,
                 ] as $unit) {
            for ($i = -15; $i < 15; $i++) {
                $timestamps[] = [$t->duplicate()->addUnit($i, $unit)->unixTimestamp()];
            }
        }
        return $timestamps;
    }

    /**
     * @param mixed $value
     * @return void
     * @covers ::unitsTranslationTable ::factory ::now ::add ::difference ::addUnit
     * @dataProvider data_valueRanges
     */
    public function test_valueRanges(mixed $value): void
    {
        $x = Timestamp::factory($value);
        $this->assertGreaterThanOrEqual(1, $x->month());
        $this->assertLessThanOrEqual(12, $x->month());

        $this->assertGreaterThanOrEqual(1, $x->day());
        $this->assertLessThanOrEqual(31, $x->day());

        $this->assertGreaterThanOrEqual(0, $x->weekday());
        $this->assertLessThanOrEqual(6, $x->weekday());

        $this->assertGreaterThanOrEqual(0, $x->yearday());
        $this->assertLessThanOrEqual(365, $x->yearday());

        $this->assertGreaterThanOrEqual(0, $x->hour());
        $this->assertLessThanOrEqual(23, $x->hour());

        $this->assertGreaterThanOrEqual(1, $x->hour12());
        $this->assertLessThanOrEqual(12, $x->hour12());

        $this->assertGreaterThanOrEqual(0, $x->minute());
        $this->assertLessThanOrEqual(59, $x->minute());

        $this->assertGreaterThanOrEqual(0, $x->second());
        $this->assertLessThanOrEqual(59, $x->second());

        $this->assertInArray($x->ampm(), [
            'am',
            'pm',
        ]);
    }

    public static function data_setYMD(): array
    {
        return [
            [
                '2012:10:11',
                2012,
                10,
                11,
            ],
        ];
    }

    /**
     * @param string $expected
     * @param int $year
     * @param int $month
     * @param int $day
     * @return void
     * @covers ::setYMD ::format
     * @dataProvider data_setYMD
     * @return void
     */
    public function test_setYMD(string $expected, int $year, int $month, int $day): void
    {
        $x = Timestamp::factory();
        $x->setYMD($year, $month, $day);
        $this->assertEquals($expected, $x->format('{YYYY}:{MM}:{DD}'));
    }

    public static function data_setDayError(): array
    {
        return [
            [
                1,
                -1,
            ],
            [
                2,
                -1,
            ],
            [
                12,
                -1,
            ],
            [
                2,
                30,
            ],
            [
                2,
                31,
            ],
            [
                1,
                32,
            ],
            [
                2,
                32,
            ],
            [
                12,
                32,
            ],
            [
                1,
                -2,
            ],
            [
                1,
                128,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function data_setMonth(): array
    {
        return [
            ['2025-10-30 01:23:45', 2, '2025-02-28 01:23:45'],
            ['2025-10-31 01:23:45', 11, '2025-11-30 01:23:45'],
            ['2025-10-31 01:23:45', 2, '2025-02-28 01:23:45'],
        ];
    }


    /**
     * @param string $startingDate
     * @param int $month
     * @param string $finalDate
     * @return void
     * @throws ParseException
     * @dataProvider data_setMonth
     * @covers       Timestamp::setMonth
     */
    public function test_setMonth(string $startingDate, int $month, string $finalDate)
    {
        $x = Timestamp::factory();
        $x->parse($startingDate);
        $x->setMonth($month);
        $this->assertEquals($finalDate, $x->format());
    }

    /**
     * @dataProvider data_setDayError
     * @param int $month
     * @param int $value
     * @return void
     * @covers ::setDay ::setMonth
     */
    public function test_setDayError(int $month, int $value): void
    {
        $x = Timestamp::factory();
        $x->setMonth($month);
        $year = $x->year();
        if ($value > 0) {
            $this->assertGreaterThan(Date::daysInMonth($month, $year), $value, "Date::daysInMonth($month, $year) = " . Date::daysInMonth($month, $year) . " < $value");
        }
        $this->expectException(OutOfBoundsException::class);
        $x->setDay($value);
    }

    /**
     * @return void
     * @covers ::setWeekday
     */
    public function test_setQuarter(): void
    {
        $x = Timestamp::factory();

        for ($value = 1; $value <= 4; $value++) {
            $before = $x->format();
            $x->setQuarter($value);
            $this->assertEquals($value, $x->quarter(), "$before => " . $x->format());
        }
    }

    public static function data_format(): array
    {
        return [
            [
                '22:23:24',
                22,
                23,
                24,
                '{hh}:{mm}:{ss}',
                false,
            ],
            [
                '10:23:24',
                22,
                23,
                24,
                '{12hh}:{mm}:{ss}',
                false,
            ],
            [
                '10:23:24 {ampm}',
                22,
                23,
                24,
                '{12hh}:{mm}:{ss} {ampm}',
                false,
            ],
            [
                '10:23:24 pm',
                22,
                23,
                24,
                '{12hh}:{mm}:{ss} {ampm}',
                true,
            ],
            [
                '10:23:24 PM',
                22,
                23,
                24,
                '{12hh}:{mm}:{ss} {AMPM}',
                true,
            ],

            [
                '10:23:24 {AMPM}',
                10,
                23,
                24,
                '{12hh}:{mm}:{ss} {AMPM}',
                false,
            ],
            [
                '10:23:24 AM',
                10,
                23,
                24,
                '{12hh}:{mm}:{ss} {AMPM}',
                true,
            ],
            [
                '10:23:24 AM',
                10,
                23,
                24,
                '{hh}:{mm}:{ss} {AMPM}',
                true,
            ],
            [
                '10:23:24 am',
                10,
                23,
                24,
                '{hh}:{mm}:{ss} {ampm}',
                true,
            ],
        ];
    }

    /**
     * @param string $expected
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @param string $format
     * @param bool $includeLocale
     * @return void
     * @covers ::format ::setHMS
     * @dataProvider data_format
     * @throws ClassNotFound
     */
    public function test_format(string $expected, int $hour, int $minute, int $second, string $format, bool $includeLocale): void
    {
        $x = Timestamp::factory()->setHMS($hour, $minute, $second);
        $args = $includeLocale ? ['locale' => $this->application->localeFactory('en_US')] : [];
        $this->assertEquals($expected, $x->format($format, $args));
    }

    public static function data_setYMDHMS(): array
    {
        return [
            [
                '2011-12-31 23:59:59',
                2011,
                12,
                31,
                23,
                59,
                59,
            ],
        ];
    }

    /**
     * @dataProvider data_setYMDHMS
     * @param string $expected
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     * @return void
     * @covers ::unitsTranslationTable ::factory ::now ::add ::difference ::addUnit
     */
    public function test_setYMDHMS(string $expected, int $year, int $month, int $day, int $hour, int $minute, int $second): void
    {
        $this->assertEquals($expected, Timestamp::factory()->setYMDHMS($year, $month, $day, $hour, $minute, $second)->format(Timestamp::DEFAULT_FORMAT_STRING));
    }

    /**
     * @return void
     * @covers ::setHour ::hour
     * @covers ::setMinute ::minute
     * @covers ::setSecond ::second
     * @covers ::compare
     */
    public function test_validRanges(): void
    {
        $x = Timestamp::factory();

        for ($i = 0; $i < 24; $i++) {
            $x->setHour($i);
            $this->assertEquals($i, $x->hour());
        }
        for ($i = 0; $i < 59; $i++) {
            $x->setMinute($i);
            $this->assertEquals($i, $x->minute());
        }
        for ($i = 0; $i < 59; $i++) {
            $x->setSecond($i);
            $this->assertEquals($i, $x->second());
        }
        $value = Timestamp::now();
        $this->assertGreaterThan(0, $x->compare($value));
        // compare -> -1 means x is BEFORE value
        // compare -> +1 means x is AFTER value
    }

    /**
     * @return void
     * @covers ::unitsTranslationTable
     */
    public function test_unitsTranslationTableBasics(): void
    {
        $tt = Timestamp::unitsTranslationTable();
        $this->assertArrayHasKeys([
            Temporal::UNIT_HOUR,
            Temporal::UNIT_SECOND,
            Temporal::UNIT_DAY,
        ], $tt);
    }

    public static function data_secondsToUnit(): array
    {
        return [
            [
                'second',
                2.0,
                2,
                'second',
            ],
            [
                'minute',
                1.0333333333333334,
                62,
                'second',
            ],
            [
                'minute',
                1.0333333333333334,
                62,
                'minute',
            ],
            [
                'minute',
                1.5,
                90,
                'minute',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param float $expectedFraction
     * @param int $seconds
     * @param string $stopAtUnit
     * @return void
     * @dataProvider data_secondsToUnit
     * @covers ::secondsToUnit
     * @return void
     */
    public function test_secondsToUnit(string $expected, float $expectedFraction, int $seconds, string $stopAtUnit): void
    {
        $this->assertEquals($expected, Timestamp::secondsToUnit($seconds, $stopAtUnit, $divide));
        $this->assertEquals($expectedFraction, $divide);
    }

    public static function data_subtract(): array
    {
        return [
            [
                0,
                '2022-11-22 17:53:23',
                '2022-11-22 17:53:23',
            ],
            [
                -1,
                '2022-11-22 17:53:23',
                '2022-11-22 17:53:24',
            ],
            [
                1,
                '2022-11-22 17:53:24',
                '2022-11-22 17:53:23',
            ],
            [
                3600,
                '2022-11-22 17:53:24',
                '2022-11-22 16:53:24',
            ],
            [
                -3600,
                '2022-11-22 16:53:24',
                '2022-11-22 17:53:24',
            ],
        ];
    }

    /**
     * @param int $expected
     * @param string $initial
     * @param string $subtract
     * @return void
     * @throws ParseException
     * @dataProvider data_subtract
     * @covers ::subtract
     */
    public function test_subtract(int $expected, string $initial, string $subtract): void
    {
        $x = Timestamp::factory()->parse($initial);
        $value = Timestamp::factory()->parse($subtract);
        $this->assertEquals($expected, $x->subtract($value));
    }

    /**
     * @return void
     * @covers ::before
     */
    public function test_beforeLogic(): void
    {
        $x = Timestamp::factory()->addUnit(-1);

        $model = Timestamp::now();
        $this->assertTrue($x->before($model));

        $this->assertTrue($x->before($x, true));
        $this->assertFalse($x->before($x));
    }

    /**
     * @return void
     * @covers ::now ::unixTimestamp
     */
    public function test_newTimestamp(): void
    {
        $model2 = Timestamp::now();
        $model = new Timestamp($model2);
        $this->assertEquals($model->unixTimestamp(), $model2->unixTimestamp());
    }

    /**
     * @return void
     * @covers ::unitsTranslationTable ::factory ::now ::add ::difference ::addUnit
     */
    public function test_after(): void
    {
        $n = Timestamp::factory();
        $x = $n->duplicate();

        $this->assertInstanceOf(Timestamp::class, $x->addUnit(1, 'second'));
        $this->assertTrue($x->after($n, true), $x->format() . ' After same object true is false?');
        $this->assertTrue($x->after($n));
        $this->assertTrue($n->before($x, true));
        $this->assertTrue($n->before($x));
    }

    public static function data_iso8601Compact(): array
    {
        return [
            ['20290101T000000', '2029-01-01 00:00:00',],
            ['20121231T235959', '2012-12-31 23:59:59',],
        ];
    }

    public static function data_setISO8601(): array
    {
        return [
            ['2999-01-01T23:59:59',],
            ['2029-01-01T00:00:00'],
            ['2012-12-31T23:59:59'],
        ];
    }

    public static function data_setISO8601Overflow(): array
    {
        return [
            ['1970-01-02T00:59:59', '1970-01-01T24:59:59',],
            ['1970-01-02T01:59:59', '1970-01-01T25:59:59',],
            ['1970-01-02T05:59:59', '1970-01-01T29:59:59',],
        ];
    }

    public static function data_setISO8601Fail(): array
    {
        return [
            ['1970-01-01T35:59:59',],
            ['1970-01-01 23:59:59',],
            ['1970-01-01T23:60:59',],
            ['1970-01-01T23:59:60',],
            ['1970-01-01T-1:59:59',],
        ];
    }

    public static function data_iso8601(): array
    {
        return [
            ['2029-01-01T00:00:00', '2029-01-01 00:00:00',],
            ['2012-12-31T23:59:59', '2012-12-31 23:59:59',],
        ];
    }

    /**
     * @throws ParseException
     * @dataProvider data_iso8601
     * @covers ::iso8601
     */
    public function test_iso8601(string $expected, string $parse): void
    {
        $x = Timestamp::factory()->parse($parse);
        $this->assertEquals($expected, $x->iso8601());
    }

    /**
     * @throws SyntaxException
     * @throws ParseException
     * @dataProvider data_iso8601Compact
     * @covers ::iso8601Compact
     */
    public function test_iso8601Compact(string $expected, string $parse): void
    {
        $x = Timestamp::factory()->parse($parse);
        $this->assertEquals($expected, $x->iso8601Compact());
        $n = Timestamp::factory()->setISO8601($x->iso8601Compact());
        $this->assertEquals($n->iso8601(), $x->iso8601());
    }

    /**
     * @throws SyntaxException
     * @covers ::setISO8601
     * @covers ::iso8601
     * @dataProvider data_setISO8601
     */
    public function test_setISO8601(string $value): void
    {
        $x = Timestamp::factory()->setISO8601($value);
        $this->assertEquals($value, $x->iso8601());
    }

    /**
     * @throws SyntaxException
     * @covers ::setISO8601
     * @covers ::iso8601
     * @dataProvider data_setISO8601Overflow
     */
    public function test_setISO8601Overflow(string $expected, string $value): void
    {
        $x = Timestamp::factory()->setISO8601($value);
        $this->assertEquals($expected, $x->iso8601());
    }

    /**
     * @param string $value
     * @return void
     * @throws SyntaxException
     * @dataProvider data_setISO8601Fail
     * @covers ::setISO8601
     */
    public function test_setISO8601Fail(string $value): void
    {
        $this->expectException(SyntaxException::class);
        Timestamp::factory()->setISO8601($value);
    }

    public static function data_parse(): array
    {
        return [
            [
                '2064-08-16 04:11:10',
                'Sat, 16-Aug-2064 04:11:10 GMT',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $parse
     * @return void
     * @covers ::parse
     * @return void
     * @throws ParseException
     * @dataProvider data_parse
     */
    public function test_parse(string $expected, string $parse): void
    {
        date_default_timezone_set('UTC');

        $test_long_date = (new Timestamp())->parse($parse);
        $this->assertEquals($expected, $test_long_date->format(Timestamp::DEFAULT_FORMAT_STRING));
    }

    /**
     * @return void
     * @covers ::difference ::addUnit ::now ::add
     */
    public function test_difference2(): void
    {
        $now = Timestamp::now();
        $last_year = Timestamp::factory($now)->add(-1);
        $units = Timestamp::unitsTranslationTable();
        foreach ($units as $unit => $seconds) {
            $this->assertGreaterThan(0, $now->difference($last_year, $unit));
            $this->assertLessThan(0, $last_year->difference($now, $unit), "$unit should be negative");
        }

        $just_a_sec = Timestamp::factory($now)->addUnit();
        foreach ($units as $unit => $seconds) {
            $this->assertEquals($just_a_sec->difference($now, $unit), intval(round(1 / $seconds)), $unit);
        }
    }

    /**
     * @covers ::parse
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
            [Timestamp::now(),],
            [Timestamp::instance(2000, 10, 2, 18, 59, 59, 123),],
            [Timestamp::instance(1970, 12, 31, 18, 59, 59, 123),],
            [Timestamp::instance(1950, 2, 14, 4, 0, 1, 412),],
            [Timestamp::instance(1900, 2, 2, 23, 59, 59, 999),],
        ];
    }

    /**
     * @dataProvider data_serializeExamples
     * @param Timestamp $ts
     * @throws SyntaxException
     * @covers ::__sleep ::__wakeup
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
