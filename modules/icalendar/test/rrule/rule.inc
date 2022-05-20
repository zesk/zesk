<?php declare(strict_types=1);
use zesk\Test_Unit;

class Test_RRule_Rule extends Test_Unit {
	protected array $load_modules = [
		'icalendar',
	];

	public function test_intdiv(): void {
		//>>> for i in range(10):
		//...     print -i / 4;
		//...
		$results = [
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
		];
		$b = 4;
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::intdiv(-$i, $b);
			$expected = $results[$i];
			$this->assert_equal($actual, $expected, 'intdiv(' . -$i . ", $b)");
		}
	}

	public function test_divmod(): void {
		//>>> for i in range(10):
		//...   print divmod(-3, i+1);
		//...
		$expected = [
			[
				-3,
				0,
			],
			[
				-2,
				1,
			],
			[
				-1,
				0,
			],
			[
				-1,
				1,
			],
			[
				-1,
				2,
			],
			[
				-1,
				3,
			],
			[
				-1,
				4,
			],
			[
				-1,
				5,
			],
			[
				-1,
				6,
			],
			[
				-1,
				7,
			],
		];
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-3, $i + 1);
			$this->assert_equal_array($actual, $expected[$i], 'divmod(-3, ' . ($i + 1) . ') ' . json_encode($actual) . ' !== ' . json_encode($expected[$i]));
		}
		//>>> for i in range(10):
		//...   print divmod(i+1, -3);
		//...
		$expected = [
			[
				-1,
				-2,
			],
			[
				-1,
				-1,
			],
			[
				-1,
				0,
			],
			[
				-2,
				-2,
			],
			[
				-2,
				-1,
			],
			[
				-2,
				0,
			],
			[
				-3,
				-2,
			],
			[
				-3,
				-1,
			],
			[
				-3,
				0,
			],
			[
				-4,
				-2,
			],
		];
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod($i + 1, -3);
			$this->assert_equal_array($actual, $expected[$i]);
		}

		//>>> for i in range(10):
		//...     print divmod(-i-1, -3);
		//...
		$expected = [
			[
				0,
				-1,
			],
			[
				0,
				-2,
			],
			[
				1,
				0,
			],
			[
				1,
				-1,
			],
			[
				1,
				-2,
			],
			[
				2,
				0,
			],
			[
				2,
				-1,
			],
			[
				2,
				-2,
			],
			[
				3,
				0,
			],
			[
				3,
				-1,
			],
		];
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-$i - 1, -3);
			$this->assert_equal_array($actual, $expected[$i], 'divmod(' . (-$i - 1) . ', -3) ' . json_encode($actual) . ' !== ' . json_encode($expected[$i]));
		}

		//>>> for i in range(10):
		//...     print divmod(-3, -i-1);
		//...
		$expected = [
			[
				3,
				0,
			],
			[
				1,
				-1,
			],
			[
				1,
				0,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
			[
				0,
				-3,
			],
		];
		for ($i = 0; $i < 10; $i++) {
			$actual = RRule\Rule::divmod(-3, -$i - 1);
			$this->assert_equal_array($actual, $expected[$i]);
		}
	}
}
