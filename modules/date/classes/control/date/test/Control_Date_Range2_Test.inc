<?php

use zesk\Timestamp as Timestamp;

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/test/Control_Date_Range2_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Control_Date_Range2_Test extends Test_Control {
	function initialize() {
		Module::load('date');
	}
	function test_main() {
		$this->test_basics(widget::factory('Control_Date_Range2'));

		$options = false;
		$testx = new Control_Date_Range($options);
	}
	function test_specifics() {
		$request = Request::instance();
		/*
		 * Specific tests
		 */
		$x = new Control_Date_Range2($request);

		$x->names("test", "Test");
		$request->set("test", "quarter-0-0");
		$object = null;
		$x->execute($object);

		$start_date = new Date($object['StartDate']);
		$end_date = new Date($object['EndDate']);

		dump($object);

		$start_date_month = $start_date->month();
		$end_date_month = $end_date->month();

		$quarter_map = array(
			1 => 1,
			2 => 1,
			3 => 1,
			4 => 2,
			5 => 2,
			6 => 2,
			7 => 3,
			8 => 3,
			9 => 3,
			10 => 4,
			11 => 4,
			12 => 4
		);

		// TODO This is brokwn, should be fixes when figure out widget operation

// 		echo "Start Date is: " . $start_date->format() . "\n";
// 		echo "  End Date is: " . $end_date->format() . "\n";

// 		/* Basic tests */
// 		$this->assert($start_date->year() === $end_date->Year());
// 		$this->assert($start_date->day() === 1, $start_date->day() . " === 1");
// 		$this->assert($start_date->hour() === 0);
// 		$this->assert($start_date->minute() === 0);
// 		$this->assert($start_date->second() === 0);

// 		$this->assert($end_date->second() === 59);
// 		$this->assert($end_date->day() === Date::daysInMonth($end_date_month, $end_date->Year()));
// 		$this->assert($end_date->hour() === 23);
// 		$this->assert($end_date->minute() === 59);
// 		$this->assert($end_date->second() === 59);

// 		$this->assert(avalue($quarter_map, $start_date_month, -1) === avalue($quarter_map, $end_date_month, -2), 'avalue($quarter_map, $start_date_month, -1) === avalue($quarter_map, $end_date_month, -2)');

// 		$now = new Timestamp("now");
// 		$current_month = $now->getMonth();

// 		$which_quarter = avalue($quarter_map, $current_month, -1);
// 		$quarter_start_month = (($which_quarter - 1) * 3) + 1;
// 		$quarter_end_month = $quarter_start_month + 2;
// 		$this->assert("$start_date_month === $quarter_start_month", '$start_date_month === $quarter_start_month');
// 		$this->assert("$end_date_month === $quarter_end_month", '$end_date_month === $quarter_end_month');

// 		$object = array(
// 			"test" => "quarter-1-1"
// 		);
	}
}
