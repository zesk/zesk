<?php
namespace zesk\Diff;

use zesk\Test_Unit;

/**
 *
 * @author kent
 *
 */
class Diff_Test extends Test_Unit {
	protected $load_modules = array(
		"diff"
	);
	function test_diff_binary() {
		$a = "a";
		$b = "a";
		$testx = new Binary($a, $b);
		$this->assert($testx->is_identical());
		$edits = $testx->edits();
		$this->assert(count($edits) === 1);
	}
	function test_diff_binary2() {
		$a = "a";
		$b = "ab";
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 1);
		$this->assert_equal_object(new Edit(Edit::DIFF_INSERT, 1, 1, 'b'), $diffs[0]);
	}
	function test_diff_binary3() {
		$a = "a";
		$b = "ba";
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 1);
		$this->assert_equal_object(new Edit(Edit::DIFF_INSERT, 0, 1, 'b'), $diffs[0]);
	}
	function test_diff_binary4() {
		$a = "ab";
		$b = "a";
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 1);
		$this->assert_equal_object(new Edit(Edit::DIFF_DELETE, 1, 1), $diffs[0]);
	}
	function test_diff_binary5() {
		$a = "ab";
		$b = "b";
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 1);
		$this->assert_equal_object(new Edit(Edit::DIFF_DELETE, 0, 1), $diffs[0]);
	}
	function test_diff_binary6() {
		$a = "Learning\nto\nuse\nthe\npotty";
		$b = "Learning\n\nto\nuse\nthe\nnew\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert_equal(count($diffs), 2);
		$this->assert_equal_object($diffs[0], new Edit(Edit::DIFF_INSERT, 9, 1, "\n"));
		$this->assert_equal_object($diffs[1], new Edit(Edit::DIFF_INSERT, 21, 4, "new\n"));
	}
	function test_diff_binary7() {
		$a = "Learning\nto\nuse\nthe\nnew\npotty";
		$b = "Learning\nto\nuse\nthe\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 1);
		$this->assert_equal_object(new Edit(Edit::DIFF_DELETE, 20, 4), $diffs[0]);
	}
	function test_diff_binary8() {
		$a = "Learning\nto\nuse\nthe\nold\npotty";

		$b = "Learning\nto\nuse\nthe\nnew\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assert(count($diffs) === 2);
		$this->assert_equal_object(new Edit(Edit::DIFF_DELETE, 20, 3), $diffs[0]);
		$this->assert_equal_object(new Edit(Edit::DIFF_INSERT, 20, 3, 'new'), $diffs[1]);
	}
	function test_diff_lines() {
		$a = "Line1\nLine2\nLine4";
		$b = "Line1\nLine2\nLine3\nLine4";
		$testx = new Lines($a, $b);

		$this->log("Sample 1: \n$a");
		$this->log("Sample 2: \n$b");
		$this->log("Output: \n" . $testx->output());

		$this->assert_equal($testx->diffs(), array(
			new Edit(Edit::DIFF_INSERT, 2, 1, array(
				"Line3"
			))
		));
		$testx = new Lines($b, $a);
		$this->assert_equal($testx->diffs(), array(
			new Edit(Edit::DIFF_DELETE, 2, 1)
		));
	}
	function test_diff_text() {
		$a = "abcdefghijklmnopqrstuvwxyz";
		$b = "abclXXXqrstwxz";
		$d0 = $d = new Binary($a, $b);
		$edits = $d->edits();
		$d1 = new Binary($b, $a);

		$this->log("Sample 1: \n$a");
		$this->log("Sample 2: \n$b");
		$this->log("Output: \n" . $d0->output());

		$this->log("Sample 1: \n$b");
		$this->log("Sample 2: \n$a");
		$this->log("Output: \n" . $d1->output());

		$diffs0 = $d0->diffs();
		$diffs1 = $d1->diffs();
		$this->assert_equal(count($diffs0), count($diffs1), count($diffs0) . " = count(diffs0) === count(diffs1) = " . count($diffs1));

		$offset0 = $offset1 = 0;
		foreach ($diffs0 as $index => $edit0) {
			/* @var $edit0 Edit */
			/* @var $edit1 Edit */
			$edit1 = avalue($diffs1, $index);
			$this->assert_not_equal($edit0->op, Edit::DIFF_MATCH);
			$this->assert_not_equal($edit1->op, Edit::DIFF_MATCH);
			$opposite = $edit0->op === Edit::DIFF_DELETE ? Edit::DIFF_INSERT : Edit::DIFF_DELETE;

			$offset0 += $edit0->off;
			$offset1 += $edit1->off;

			echo " Offsets: $offset0 $offset1\n";
			if ($edit0->op === Edit::DIFF_INSERT) {
				$offset0 += $edit0->len;
			} else {
				$offset0 -= $edit0->len;
			}
			if ($edit1->op === Edit::DIFF_INSERT) {
				$offset1 += $edit1->len;
			} else {
				$offset1 -= $edit1->len;
			}
			//echo " +Length: $offset0 $offset1\n";
			// 			$this->assert_equal($edit0->off, $edit1->off);
			// 			$this->assert_equal($edit0->len, $edit1->len);
			// TODO Compute offset and match correctly
		}
	}

	/*
	 Sample 1: abcdefghijklmnopqrstuvwxyz
	 Sample 2: abclXXXqrstwxz
	 Output:
	 <3 (8)
	 defghijk
	 <12 (4)
	 mnop
	 >4 (3)
	 XXX
	 <20 (2)
	 uv
	 <24 (1)
	 y
	 Sample 2: abc defghijk l <<< mnop qrst uv wx y z
	 Sample 1: abc >>>>>>>> l XXX >>>> qrst >> wx > z
	 Output:
	 >3 (8)
	 defghijk
	 <4 (3)
	 XXX
	 >12 (4)
	 mnop
	 >20 (2)
	 uv
	 >24 (1)
	 y
	 */
}
