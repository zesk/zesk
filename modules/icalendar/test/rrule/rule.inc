<?php
use zesk\Test_Unit;

class Test_RRule_Rule extends Test_Unit {
	protected $load_modules = array(
		"icalendar",
	);

	public function test_intdiv() {
		//>>> for i in range(10):
		//...     print -i / 4;
		//...
		$results = array(
			0,
			-1,
			-1,
			-1,
			-1,
			-2,
			-2,
			-2,
			-2,
			-3,
		);
		$b = 4;
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::intdiv(-$i, $b);
			$expected = $results[$i];
			$this->assert_equal($actual, $expected, "intdiv(" . -$i . ", $b)");
		}
	}

	public function test_divmod() {
		//>>> for i in range(10):
		//...   print divmod(-3, i+1);
		//...
		$expected = array(
			array(
				-3,
				0,
			),
			array(
				-2,
				1,
			),
			array(
				-1,
				0,
			),
			array(
				-1,
				1,
			),
			array(
				-1,
				2,
			),
			array(
				-1,
				3,
			),
			array(
				-1,
				4,
			),
			array(
				-1,
				5,
			),
			array(
				-1,
				6,
			),
			array(
				-1,
				7,
			),
		);
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-3, $i + 1);
			$this->assert_equal_array($actual, $expected[$i], "divmod(-3, " . ($i + 1) . ") " . json_encode($actual) . " !== " . json_encode($expected[$i]));
		}
		//>>> for i in range(10):
		//...   print divmod(i+1, -3);
		//...
		$expected = array(
			array(
				-1,
				-2,
			),
			array(
				-1,
				-1,
			),
			array(
				-1,
				0,
			),
			array(
				-2,
				-2,
			),
			array(
				-2,
				-1,
			),
			array(
				-2,
				0,
			),
			array(
				-3,
				-2,
			),
			array(
				-3,
				-1,
			),
			array(
				-3,
				0,
			),
			array(
				-4,
				-2,
			),
		);
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod($i + 1, -3);
			$this->assert_equal_array($actual, $expected[$i]);
		}

		//>>> for i in range(10):
		//...     print divmod(-i-1, -3);
		//...
		$expected = array(
			array(
				0,
				-1,
			),
			array(
				0,
				-2,
			),
			array(
				1,
				0,
			),
			array(
				1,
				-1,
			),
			array(
				1,
				-2,
			),
			array(
				2,
				0,
			),
			array(
				2,
				-1,
			),
			array(
				2,
				-2,
			),
			array(
				3,
				0,
			),
			array(
				3,
				-1,
			),
		);
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-$i - 1, -3);
			$this->assert_equal_array($actual, $expected[$i], "divmod(" . (-$i - 1) . ", -3) " . json_encode($actual) . " !== " . json_encode($expected[$i]));
		}

		//>>> for i in range(10):
		//...     print divmod(-3, -i-1);
		//...
		$expected = array(
			array(
				3,
				0,
			),
			array(
				1,
				-1,
			),
			array(
				1,
				0,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
			array(
				0,
				-3,
			),
		);
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-3, -$i - 1);
			$this->assert_equal_array($actual, $expected[$i]);
		}
	}
}
