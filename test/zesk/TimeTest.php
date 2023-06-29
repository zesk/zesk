<?php
declare(strict_types=1);

namespace zesk;

use OutOfBoundsException;
use zesk\Exception\ParameterException;

class TimeTest extends UnitTest
{
	public function test_instance(): void
	{
		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);
	}

	public function test_parse(): void
	{
		$x = new Time();

		$x = $x->parse('23:29:19');
		$this->assertEquals($x->format('{hh}:{mm}:{ss}'), '23:29:19');
		$this->assertEquals(23, $x->hour());
		$this->assertEquals(29, $x->minute());
		$this->assertEquals(19, $x->second());
	}

	public function test_parse_fail(): void
	{
		$x = new Time();

		$this->expectException(OutOfBoundsException::class);
		$x->parse('23:61:19');
	}

	public function test_basics(): void
	{
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

		$x->setHMS(); // 0 0 0

		$this->assertEquals(0, $x->seconds());
		$value = 14;
		$x->setHour($value);

		$value = 54;
		$x->setMinute($value);

		$value = 22;
		$x->setSecond($value);

		$this->assertEquals(14, $x->hour());

		$this->assertEquals(2, $x->hour12());

		$this->assertEquals('pm', $x->ampm());

		$this->assertEquals(54, $x->minute());

		$this->assertEquals(22, $x->second());

		$this->assertEquals(53662, $x->seconds());

		$value = new Time();
		$value->parse('12:22:22');
		$this->assertGreaterThan(0, $x->compare($value));

		$this->assertEquals(0, $value->compare($value));
		$value = new Time();
		$value->parse('00:00:01');
		$this->assertEquals('14:54:22', $x->format());
		$this->assertEquals(53661, $x->subtract($value));
		$this->assertEquals('14:54:22', $x->format());

		$old = clone $x;
		$x->add(0, 0, 0);
		$this->assertEquals($old, $x);

		$format_string = '{hh}:{mm}:{ss}';
		$this->assertEquals('14:54:22', $x->format($format_string));

		$unit = Timestamp::UNIT_SECOND;
		$n = 1;
		$this->assertEquals($x, $x->addUnit($n, $unit));
		$this->assertEquals('14:54:23', $x->format());
		$this->assertEquals($x, $x->addUnit(10, Temporal::UNIT_MINUTE));
		$this->assertEquals('15:04:23', $x->format());
		$this->assertEquals($x, $x->addUnit(8, Temporal::UNIT_HOUR));
		$this->assertEquals('23:04:23', $x->format());
		$this->assertEquals($x, $x->addUnit(58, Temporal::UNIT_MINUTE));
		$this->assertEquals('00:02:23', $x->format());
	}

	public function test_invalid_unit(): void
	{
		$this->expectException(ParameterException::class);
		$time = new Time();
		$time->addUnit(1, 'money');
	}
}
