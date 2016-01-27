<?php
class test_range extends Test_Unit {

	function test_overlap() {
		$a0 = null;
		$a1 = null;
		$b0 = null;
		$b1 = null;
		$a0i = false;
		$a1i = false;
		$b0i = false;
		$b1i = false;
		$this->assert(range::overlap(1, 2, 3, 4, 0, 1, 2, 3) === false);
		$this->assert(range::overlap(3, 4, 1, 2, 0, 1, 2, 3) === false);

		$a = range::overlap(1, 3, 2, 4, 0, 1, 2, 3);
		$b = array(
			1,
			4,
			0,
			3
		);
		dump($a);
		dump($b);
		$this->assert_arrays_equal($a, $b);

		$this->assert_arrays_equal(range::overlap(0, 10, 3, 4, 5, 6, 7, 8), array(
			0,
			10,
			5,
			6
		));
	}
}
