<?php
declare(strict_types=1);

namespace zesk\Diff;

use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class DiffTest extends TestCase {
	protected array $load_modules = [
		'diff',
	];

	public function test_diff_binary(): void {
		$a = 'a';
		$b = 'a';
		$testx = new Binary($a, $b);
		$this->assertTrue($testx->isIdentical());
		$edits = $testx->edits();
		$this->assertCount(1, $edits);
	}

	public function test_diff_binary2(): void {
		$a = 'a';
		$b = 'ab';
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertCount(1, $diffs);
		$this->assertEquals(new Edit(Edit::DIFF_INSERT, 1, 1, 'b'), $diffs[0]);
	}

	public function test_diff_binary3(): void {
		$a = 'a';
		$b = 'ba';
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertEquals(1, count($diffs));
		$this->assertEquals(new Edit(Edit::DIFF_INSERT, 0, 1, 'b'), $diffs[0]);
	}

	public function test_diff_binary4(): void {
		$a = 'ab';
		$b = 'a';
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertEquals(1, count($diffs));
		$this->assertEquals(new Edit(Edit::DIFF_DELETE, 1, 1), $diffs[0]);
	}

	public function test_diff_binary5(): void {
		$a = 'ab';
		$b = 'b';
		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertEquals(1, count($diffs));
		$this->assertEquals(new Edit(Edit::DIFF_DELETE, 0, 1), $diffs[0]);
	}

	public function test_diff_binary6(): void {
		$a = "Learning\nto\nuse\nthe\npotty";
		$b = "Learning\n\nto\nuse\nthe\nnew\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertCount(2, $diffs);
		$this->assertEquals($diffs[0], new Edit(Edit::DIFF_INSERT, 9, 1, "\n"));
		$this->assertEquals($diffs[1], new Edit(Edit::DIFF_INSERT, 21, 4, "new\n"));
	}

	public function test_diff_binary7(): void {
		$a = "Learning\nto\nuse\nthe\nnew\npotty";
		$b = "Learning\nto\nuse\nthe\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertCount(1, $diffs);
		$this->assertEquals(new Edit(Edit::DIFF_DELETE, 20, 4), $diffs[0]);
	}

	public function test_diff_binary8(): void {
		$a = "Learning\nto\nuse\nthe\nold\npotty";

		$b = "Learning\nto\nuse\nthe\nnew\npotty";

		$testx = new Binary($a, $b);
		$diffs = $testx->diffs();
		$this->assertCount(2, $diffs);
		$this->assertEquals(new Edit(Edit::DIFF_DELETE, 20, 3), $diffs[0]);
		$this->assertEquals(new Edit(Edit::DIFF_INSERT, 20, 3, 'new'), $diffs[1]);
	}

	public function test_diff_lines(): void {
		$a = "Line1\nLine2\nLine4";
		$b = "Line1\nLine2\nLine3\nLine4";
		$testx = new Lines($a, $b);

		$this->log("Sample 1: \n$a");
		$this->log("Sample 2: \n$b");
		$this->log("Output: \n" . $testx->output());

		$this->assertEquals([
			new Edit(Edit::DIFF_INSERT, 2, 1, [
				'Line3',
			]),
		], $testx->diffs());
		$testx = new Lines($b, $a);
		$this->assertEquals([
			new Edit(Edit::DIFF_DELETE, 2, 1),
		], $testx->diffs());
	}

	public function test_diff_text(): void {
		$a = 'abcdefghijklmnopqrstuvwxyz';
		$b = 'abclXXXqrstwxz';
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
		$this->assertEquals(count($diffs0), count($diffs1), count($diffs0) . ' = count(diffs0) === count(diffs1) = ' . count($diffs1));

		$offset0 = $offset1 = 0;
		foreach ($diffs0 as $index => $edit0) {
			/* @var $edit0 Edit */
			/* @var $edit1 Edit */
			$edit1 = $diffs1[$index] ?? null;
			$this->assertNotEquals($edit0->op, Edit::DIFF_MATCH);
			$this->assertNotEquals($edit1->op, Edit::DIFF_MATCH);
			$opposite = $edit0->op === Edit::DIFF_DELETE ? Edit::DIFF_INSERT : Edit::DIFF_DELETE;

			$offset0 += $edit0->off;
			$offset1 += $edit1->off;

			// echo " Offsets: $offset0 $offset1\n";
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
			// 			$this->assertEquals($edit0->off, $edit1->off);
			// 			$this->assertEquals($edit0->len, $edit1->len);
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
