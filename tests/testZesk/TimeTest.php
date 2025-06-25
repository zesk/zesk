<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use OutOfBoundsException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Temporal;
use zesk\Time;

class TimeTest extends TestCase
{
	public static function dataParse(): array
	{
		return [
			[23, 29, 19, '23:29:19'],
		];
	}

	public static function dataParseFail(): array
	{
		return [
			['23:61:19'],
			['23:23:61'],
		];
	}

	public static function dataInstance(): array
	{
		return [
			['00:00:00', 0, 0, 0, ],
			['00:00:01', 0, 0, 1, ],
			['00:01:00', 0, 1, 0, ],
			['12:12:12', 12, 12, 12, ],
			['23:59:59', 23, 59, 59, ],
		];
	}

	/**
	 * @param string $expected
	 * @param int $hh
	 * @param int $mm
	 * @param int $ss
	 * @return void
	 * @dataProvider dataInstance
	 * @covers ::instance
	 */
	public function testInstance(string $expected, int $hh, int $mm, int $ss): void
	{
		$this->assertEquals($expected, Time::instance($hh, $mm, $ss)->format());
	}

	public static function dataInstanceFail(): array
	{
		return [
			[0, 0, 60, ],
			[25, 12, 12, ],
			[23, 0, 60, ],
		];
	}

	/**
	 * @param int $hh
	 * @param int $mm
	 * @param int $ss
	 * @return void
	 * @dataProvider dataInstanceFail
	 * @covers ::instance
	 */
	public function testInstanceFail(int $hh, int $mm, int $ss): void
	{
		$this->expectException(OutOfBoundsException::class);
		Time::instance($hh, $mm, $ss)->format();
	}

	/**
	 * @param int $h
	 * @param int $m
	 * @param int $s
	 * @param string $test
	 * @return void
	 * @throws ParseException
	 * @dataProvider dataParse
	 * @covers ::parse ::hour ::minute ::second
	 */
	public function testParse(int $h, int $m, int $s, string $test): void
	{
		$x = new Time();
		$this->assertNotNull($x->parse($test));
		$this->assertEquals($h, $x->hour());
		$this->assertEquals($m, $x->minute());
		$this->assertEquals($s, $x->second());
	}

	/**
	 * @param string $test
	 * @return void
	 * @throws ParseException
	 * @dataProvider dataParseFail
	 * @covers ::parse
	 */
	public function testParseFail(string $test): void
	{
		$this->expectException(OutOfBoundsException::class);
		$x = new Time();
		$x->parse($test);
	}

	/**
	 * @return void
	 * @covers ::now ::setNow
	 */
	public function testNow(): void
	{
		$x = Time::now();
		$this->assertNotNull($x->setNow());
	}

	public static function dataHour(): array
	{
		return self::dataInsideRange(0, 23);
	}

	public static function dataHourFail(): array
	{
		return self::dataOutsideRange(0, 23);
	}

	public static function dataOutsideRange(int $minimum, int $maximum): array
	{
		$rr = [];
		for ($i = $maximum + 1; $i <= 0x7FFFFFF; $i += random_int(1, mt_getrandmax())) {
			$rr[] = [$i];
		}
		for ($i = $minimum - 1; $i > -0x7FFFFFF; $i -= random_int(1, mt_getrandmax())) {
			$rr[] = [$i];
		}
		return $rr;
	}

	public static function dataInsideRange(int $minimum, int $maximum): array
	{
		$rr = [];
		for ($i = $minimum; $i <= $maximum; $i += 1) {
			$rr[] = [$i, $i];
		}
		return $rr;
	}

	public static function dataMinuteFail(): array
	{
		return self::dataOutsideRange(0, 59);
	}

	public static function dataSecondFail(): array
	{
		return self::dataOutsideRange(0, 59);
	}

	public static function dataMinute(): array
	{
		return self::dataInsideRange(0, 59);
	}

	public static function dataSecond(): array
	{
		return self::dataInsideRange(0, 59);
	}

	public static function dataSeconds(): array
	{
		$index = 2;
		$tests = [
			[0, 0],
			[1, 1],
		];
		while ($index < 86398) {
			$tests[] = [$index, $index];
			$index += random_int(1, 99);
		}
		return array_merge(
			$tests,
			[
				[86398, 86398],
				[86399, 86399],
				[0, 86400],
				[1, 86401],
			]
		);
	}

	public static function dataHour12(): array
	{
		$time = Time::now();
		return [
			[12, $time, 0],

			[1, $time, 1],
			[2, $time, 2],
			[3, $time, 3],
			[4, $time, 4],
			[5, $time, 5],
			[6, $time, 6],
			[7, $time, 7],
			[8, $time, 8],
			[9, $time, 9],
			[10, $time, 10],
			[11, $time, 11],
			[12, $time, 12],

			[1, $time, 13],
			[2, $time, 14],
			[3, $time, 15],
			[4, $time, 16],
			[5, $time, 17],
			[6, $time, 18],
			[7, $time, 19],
			[8, $time, 20],
			[9, $time, 21],
			[10, $time, 22],
			[11, $time, 23],
		];
	}

	public static function dataAMPM(): array
	{
		return [
			['am', '00:59:59'],
			['am', '01:59:59'],
			['am', '05:59:59'],
			['am', '08:59:59'],
			['am', '09:59:59'],
			['am', '11:59:59'],
			['pm', '12:00:00'],
			['pm', '12:59:59'],
			['pm', '13:59:59'],
			['pm', '23:59:59'],
		];
	}

	/**
	 * @param int $expected
	 * @param int $test
	 * @return void
	 * @dataProvider dataHour
	 * @covers ::setHour ::hour
	 */
	public function testHour(int $expected, int $test): void
	{
		$x = Time::instance();
		$this->assertEquals($expected, $x->setHour($test)->hour(), $x->format());
	}

	/**
	 * @param int $test
	 * @return void
	 * @throws OutOfBoundsException
	 * @dataProvider dataHourFail
	 * @covers ::setHour ::hour
	 */
	public function testHourFail(int $test): void
	{
		$x = Time::instance();
		$this->expectException(OutOfBoundsException::class);
		$x->setHour($test)->hour();
	}

	/**
	 * @param int $test
	 * @return void
	 * @dataProvider dataMinute
	 * @covers ::setMinute ::minute
	 */
	public function testMinute(int $expected, int $test): void
	{
		$x = Time::instance();
		$this->assertEquals($expected, $x->setMinute($test)->minute(), $x->format());
	}

	/**
	 * @param int $test
	 * @return void
	 * @throws OutOfBoundsException
	 * @dataProvider dataMinuteFail
	 * @covers ::setHour ::hour
	 */
	public function testMinuteFail(int $test): void
	{
		$x = Time::instance();
		$this->expectException(OutOfBoundsException::class);
		$x->setMinute($test)->minute();
	}

	/**
	 * @param int $expected
	 * @param int $test
	 * @return void
	 * @dataProvider dataSecond
	 * @covers ::setSecond ::second
	 */
	public function testSecond(int $expected, int $test): void
	{
		$x = Time::instance();
		$this->assertEquals($expected, $x->setSecond($test)->second(), $x->format());
	}

	/**
	 * @param int $test
	 * @return void
	 * @throws OutOfBoundsException
	 * @dataProvider dataSecondFail
	 * @covers ::setHour ::hour
	 */
	public function testSecondFail(int $test): void
	{
		$x = Time::instance();
		$this->expectException(OutOfBoundsException::class);
		$x->setSecond($test)->second();
	}

	/**
	 * @param int $expected
	 * @param int $test
	 * @return void
	 * @dataProvider dataSeconds
	 * @covers ::seconds ::setSeconds
	 */
	public function testSeconds(int $expected, int $test): void
	{
		$x = Time::instance();
		$this->assertEquals($expected, $x->setSeconds($test)->seconds(), $x->format());
	}

	/**
	 * @param int $expected
	 * @param Time $time
	 * @param int $test
	 * @return void
	 * @dataProvider dataHour12
	 * @covers ::setHour ::hour12
	 */
	public function testHour12(int $expected, Time $time, int $test): void
	{
		$this->assertEquals($expected, $time->setHour($test)->hour12(), $time->format());
	}

	/**
	 * @param string $expected
	 * @param string $time
	 * @return void
	 * @throws ParseException
	 * @dataProvider dataAMPM
	 * @covers ::ampm
	 */
	public function testAMPM(string $expected, string $time): void
	{
		$x = Time::instance()->parse($time);
		$this->assertEquals($expected, $x->ampm(), $x->format());
	}

	/**
	 * @param int $result
	 * @param string $left
	 * @param string $right
	 * @return void
	 * @throws ParseException
	 * @dataProvider dataCompare
	 * @covers ::compare
	 */
	public function testCompare(int $result, string $left, string $right): void
	{
		$leftTime = Time::factory()->parse($left);
		$rightTime = Time::factory()->parse($right);
		// $leftTime < $rightTime -> -1 -> $leftTime->compare($rightTime) < 0
		// $leftTime > $rightTime -> 1 -> $leftTime->compare($rightTime) > 0
		$this->assertEquals($result, $leftTime->compare($rightTime));
		$this->assertEquals(-$result, $rightTime->compare($leftTime));
	}

	public static function dataCompare(): array
	{
		return [
			[0, '15:04:23', '15:04:23'],
			[0, '00:00:00', '00:00:00'],
			[0, '23:59:59', '23:59:59'],

			[-1, '00:00:00', '15:04:23'],
			[-1, '00:00:00', '00:00:01'],
			[-1, '00:00:00', '23:59:59'],

			[1, '15:04:23', '00:00:00'],
			[1, '00:00:01', '00:00:00'],
			[1, '23:59:59', '00:00:00'],
			[1, '23:59:59', '23:59:58'],
		];
	}

	public static function dataAdd(): array
	{
		return [
			['15:04:23', '15:04:23', 0, 0, 0],
			['15:04:24', '15:04:23', 0, 0, 1],
			['15:05:23', '15:04:23', 0, 1, 0],
			['16:04:23', '15:04:23', 1, 0, 0],
			['14:04:23', '15:04:23', 23, 0, 0],
		];
	}

	/**
	 * @param string $expected
	 * @param string $start
	 * @param int $addHour
	 * @param int $addMinute
	 * @param int $addSecond
	 * @return void
	 * @throws ParseException
	 * @dataProvider dataAdd
	 * @covers ::add
	 */
	public function testAdd(string $expected, string $start, int $addHour, int $addMinute, int $addSecond): void
	{
		$x = Time::factory()->parse($start);
		$this->assertEquals($expected, $x->add($addHour, $addMinute, $addSecond)->format());
	}

	public static function dataAddUnit(): array
	{
		return [
			['15:04:24', '15:04:23', 1, Temporal::UNIT_SECOND],
			['15:05:23', '15:04:23', 1, Temporal::UNIT_MINUTE],
			['16:04:23', '15:04:23', 1, Temporal::UNIT_HOUR],
			['15:04:23', '15:04:23', 1, Temporal::UNIT_DAY],
		];
	}

	/**
	 * @param string $expected
	 * @param string $start
	 * @param int $count
	 * @param string $unit
	 * @return void
	 * @throws ParameterException
	 * @throws ParseException
	 * @dataProvider dataAddUnit
	 * @covers ::addUnit
	 */
	public function testAddUnit(string $expected, string $start, int $count, string $unit): void
	{
		$x = Time::factory()->parse($start);
		$this->assertEquals($expected, $x->addUnit($count, $unit)->format());
	}

	public static function dataUnitFail(): array
	{
		return [
			['money'],
			['avarice'],
			[''],
		];
	}

	/**
	 * @param string $badUnit
	 * @return void
	 * @throws ParameterException
	 * @dataProvider dataUnitFail
	 * @covers ::addUnit
	 */
	public function testUnitFail(string $badUnit): void
	{
		$this->expectException(ParameterException::class);
		$time = new Time();
		$time->addUnit(1, $badUnit);
	}
}
