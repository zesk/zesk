<?php
declare(strict_types=1);

namespace zesk;

use OutOfBoundsException;

class Time_Test extends UnitTest {
	public function test_instance(): void {
		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);
	}

	public function test_parse(): void {
		$x = new Time();

		$x = $x->parse('23:29:19');
		$this->assertEquals($x->format(null, '{hh}:{mm}:{ss}'), '23:29:19');
		$this->assertEquals(23, $x->hour());
		$this->assertEquals(29, $x->minute());
		$this->assertEquals(19, $x->second());
	}

	public function test_parse_fail(): void {
		$x = new Time();

		$this->expectException(OutOfBoundsException::class);
		$x->parse('23:61:19');
	}

	public function test_basics(): void {
		$value = null;
		$x = new Time($value);

		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);

		$x = Time::now();
		$this->assertFalse($x->isEmpty());
		$this->assertTrue($x->set(null)->isEmpty());
		$this->assertTrue($x->isEmpty());
		$this->assertFalse($x->set(1234)->isEmpty());
		$this->assertFalse($x->isEmpty());

		$this->assertTrue($x->setEmpty()->isEmpty());
		$this->assertTrue($x->isEmpty());

		$x->setNow();
		$this->assertFalse($x->isEmpty());

		$hh = 0;
		$mm = 0;
		$ss = 0;
		$x->hms($hh, $mm, $ss);

		$this->assertEquals(0, $x->seconds());
		$value = 1;
		$x->setHour($value);

		$value = 54;
		$x->setMinute($value);

		$value = 22;
		$x->setSecond($value);

		$x->hour();

		$x->hour12();

		$x->ampm();

		$x->minute();

		$x->second();

		$x->seconds();

		$value = new Time();
		$x->compare($value);

		$this->assertEquals(0, $value->compare($value));
		$value = new Time();
		$x->subtract($value);

		$hh = 0;
		$mm = 0;
		$ss = 0;
		$x->add($hh, $mm, $ss);

		$locale = null;
		$format_string = '{hh}:{mm}:{ss}';
		$x->format($locale, $format_string);

		$unit = Timestamp::UNIT_SECOND;
		$n = 1;
		$x->addUnit($n, $unit);
	}

	public function test_invalid_unit(): void {
		$this->expectException(Exception_Parameter::class);
		$time = new Time();
		$time->addUnit(1, 'money');
	}
}
