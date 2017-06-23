<?php

namespace zesk;

class Test_Time extends Test_Unit {

	function test_instance() {
		$hh = 0;
		$mm = 0;
		$ss = 0;
		Time::instance($hh, $mm, $ss);
		echo basename(__FILE__) . ": success\n";
	}

	/**
	 * @expected_exception Exception_Parameter
	 */
	function test_invalid_set() {
		$x = new Time();
		$x->unix_timestamp(true);
	}

	function test_basics() {
		$value = null;
		$options = null;
		$x = new Time($value, $options);

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

		$value = null;
		$x->parse("23:12:19");
		$this->assert($x->hour() === 23);
		$this->assert($x->minute() === 12);
		$this->assert($x->second() === 19);

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

		$format_string = '{hh}:{mm}:{ss}';
		$x->format($format_string);

		$unit = Timestamp::UNIT_SECOND;
		$n = 1;
		$x->add_unit($n, $unit);
	}

	/**
	 * @expected_exception Exception_Parameter
	 */
	function test_invalid_unit() {
		$time = new Time();
		$time->add_unit(1, "money");
	}
}
