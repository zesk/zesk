<?php declare(strict_types=1);
namespace zesk;

class Test_Time extends Test_Unit {
	public function test_instance(): void {
		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_invalid_set(): void {
		$x = new Time();
		$x->unix_timestamp(true);
	}

	public function test_parse(): void {
		$x = new Time();

		$value = null;
		$x->parse('23:29:19');
		$this->assert_equal($x->format(null, '{hh}:{mm}:{ss}'), '23:29:19');
		$this->assert_equal($x->hour(), 23);
		$this->assert_equal($x->minute(), 29);
		$this->assert_equal($x->second(), 19);
	}

	/**
	 * @expectedException zesk\Exception_Range
	 */
	public function test_parse_fail(): void {
		$x = new Time();

		$value = null;
		$x->parse('23:61:19');
	}

	public function test_basics(): void {
		$value = null;
		$x = new Time($value);

		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);

		$x->now();

		$value = null;
		$x->set($value);

		$x->is_empty();

		$x->set_empty();

		$x->set_now();

		$hh = 0;
		$mm = 0;
		$ss = 0;
		$x->hms($hh, $mm, $ss);

		$this->assert($x->seconds() === 0);
		$value = 1;
		$x->hour($value);

		$value = null;
		$x->minute($value);

		$value = null;
		$x->second($value);

		$x->hour();

		$x->hour12();

		$x->ampm();

		$x->minute();

		$x->second();

		$x->seconds();

		$value = new Time();
		$x->compare($value);

		$this->assert($value->compare($value) === 0);
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
		$x->add_unit($n, $unit);
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_invalid_unit(): void {
		$time = new Time();
		$time->add_unit(1, 'money');
	}
}
