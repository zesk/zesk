<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

/**
 * Generic test class
 */
class TimeSpan_Test extends UnitTest {
	public static function data_TimeSpan(): array {
		return [
			['s:0 00 m:0 00 h:0 00 d:0 00 000', '0', 0, ],
			['s:0 00 m:0 00 h:0 00 d:0 00 000', '0', 0.0, ],
			['s:1 01 m:0 00 h:0 00 d:0 00 000', '1', 1.0, ],
			['s:1 01 m:0 00 h:0 00 d:0 00 000', '1', -1.0, ],
			['s:1 01 m:0 00 h:0 00 d:0 00 000', '1', -1, ],
			['s:0 00 m:0 00 h:0 00 d:0 00 000', '0', '0', ],
			['s:1 01 m:0 00 h:0 00 d:0 00 000', '1', '1', ],
			['s:86400 00 m:1440 00 h:24 00 d:1 01 001', strval(Temporal::SECONDS_PER_DAY), '1 day', ],
			['s:31622400 00 m:527040 00 h:8784 00 d:366 366 001', strval(Temporal::SECONDS_PER_DAY * 366), '366 days', ],
			['s:31536000 00 m:525600 00 h:8760 00 d:365 365 000', strval(Temporal::SECONDS_PER_DAY * 365), 'next year', ],
		];
	}

	/**
	 * TimeSpan tests
	 *
	 * @param string $expected_format
	 * @param string $expected_sql
	 * @param string|float|int $init
	 * @return void
	 * @dataProvider data_TimeSpan
	 */
	public function test_TimeSpan(string $expected_format, string $expected_sql, string|float|int $init): void {
		$ts = TimeSpan::factory($init);
		$actual = $ts->format(null, 's:{seconds} {ss} m:{minutes} {mm} h:{hours} {hh} d:{days} {dd} {ddd}');
		$this->assertEquals($expected_format, $actual);
		$this->assertEquals($expected_sql, $ts->sql());

		$ts->setSeconds($init);
		$actual = $ts->format(null, 's:{seconds} {ss} m:{minutes} {mm} h:{hours} {hh} d:{days} {dd} {ddd}');
		$this->assertEquals($expected_format, $actual);
		$this->assertEquals($expected_sql, $ts->sql());

		/* Blank format is just seconds as a string */
		$this->assertEquals($expected_sql, $ts->format());
		$this->assertEquals($expected_sql, $ts->format($this->application->locale));
		$this->assertEquals($expected_sql, $ts->format($this->application->locale, '{seconds}'));
	}

	/**
	 * @return array
	 */
	public static function data_badTimeSpan(): array {
		return [
			['3 negarinos ago'],
			['not a date'],
			['99.64.1.2'],
		];
	}

	/**
	 * @param string $init
	 * @return void
	 * @throws Exception_Syntax
	 * @dataProvider data_badTimeSpan
	 */
	public function test_badTimeSpan(string $init): void {
		$this->expectException(Exception_Syntax::class);
		TimeSpan::factory($init);
	}

	public static function data_add(): array {
		$randomInt = $this->randomInteger();
		return [
			['s:0 00 m:0 00 h:0 00 d:0 00 000', -1, 1, ],
			['s:0 00 m:0 00 h:0 00 d:0 00 000', -2, 2, ],
			['s:0 00 m:0 00 h:0 00 d:0 00 000', -$randomInt, $randomInt, ],
			['s:1000 40 m:16 16 h:0 00 d:0 00 000', 1000, 0, ],
			['s:4600 40 m:76 16 h:1 01 d:0 00 000', 1000, 3600, ],
		];
	}

	/**
	 * TimeSpan tests
	 *
	 * @param string $expected_format
	 * @param string $expected_sql
	 * @param string|float|int $init
	 * @return void
	 * @dataProvider data_add
	 */
	public function test_add(string $expected_format, int $add, string|float|int $init): void {
		$ts = TimeSpan::factory($init)->add($add);
		$actual = $ts->format(null, 's:{seconds} {ss} m:{minutes} {mm} h:{hours} {hh} d:{days} {dd} {ddd}');
		$this->assertEquals($expected_format, $actual);
	}
}
