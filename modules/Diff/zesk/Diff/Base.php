<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Diff;

use zesk\Exception\NotFoundException;

use zesk\Kernel;

echo Kernel::callingFunction() . "\n";
/*
 * diff - compute shortest edit script (SES) given two sequences
 * Copyright (c) 2004 Michael B. Allen <mba2000 ioplex.com>
 *
 * Ported to PHP by Kent M. Davidson
 * kent -at- see the copyright company name no inc or dashes dot com
 *
 * The MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

/*
 * This algorithm is basically Myers' solution to SES/LCS with
 * the Hirschberg linear space refinement as described in the
 * following publication:
 *
 *   E. Myers, ``An O(ND) Difference Algorithm and Its Variations,''
 *   Algorithmica 1, 2 (1986), 251-266.
 *   http://www.cs.arizona.edu/people/gene/PAPERS/diff.ps
 *
 * This is the same algorithm used by GNU diff(1).
 */

/**
 *
 * @author kent
 *
 */
class Base
{
	/**
	 * Track state of matching
	 *
	 * @var array of integer
	 */
	private array $buf;

	/**
	 * Maximum
	 *
	 * @var int
	 */
	private int $distanceMaximum;

	/**
	 * List of edits to make A match B
	 *
	 * @var array of Edit
	 */
	private array $edits;

	/**
	 * List of edits to make A match B, excluding Edit::DIFF_MATCH
	 *
	 * @var array of Edit
	 */
	private array $diffs;

	/**
	 * Compare A
	 *
	 * @var array
	 */
	private array $left;

	/**
	 * Compare B
	 *
	 * @var array
	 */
	private array $right;

	/**
	 * Create a diff object and compute the shortest edit sequence to make $a like $b
	 *
	 * @param array $left
	 * @param array $right
	 * @param int $distanceMaximum Maximum distance to search for matches.
	 * @throws NotFoundException
	 */
	public function __construct(array $left, array $right, int $distanceMaximum = 0)
	{
		$this->buf = [];
		$this->edits = [];
		$this->diffs = [];

		$this->left = $left;
		$this->right = $right;

		$n = count($left);
		$m = count($right);

		$this->distanceMaximum = $distanceMaximum > 0 ? $distanceMaximum : $n + $m + 2;
		/*
		 * The _ses function assumes the SES will begin or end with a delete
		 * or insert. The following will ensure this is true by eating any
		 * beginning matches. This is also a quick to process sequences
		 * that match entirely.
		 */
		$x = 0;
		while ($x < $n && $x < $m && $this->left[$x] === $this->right[$x]) {
			$x++;
		}
		$y = $x;

		$this->_edit(Edit::DIFF_MATCH, 0, $x);

		$this->_ses($x, $n - $x, $y, $m - $y);

		$this->buf = [];
	}

	/**
	 *
	 * @return Edit[]
	 */
	public function edits(): array
	{
		return $this->edits;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isIdentical(): bool
	{
		return (count($this->diffs()) === 0);
	}

	/**
	 *
	 * @return Edit[]
	 */
	public function diffs(): array
	{
		if (count($this->diffs) !== 0) {
			return $this->diffs;
		}
		$diffs = [];
		foreach ($this->edits as $edit) {
			if ($edit->op !== Edit::DIFF_MATCH) {
				$diffs[] = $edit;
			}
		}
		$this->diffs = $diffs;
		return $diffs;
	}

	private static function index(int $k, int $r): int
	{
		return ($k <= 0) ? (-$k * 4 + $r) : ($k * 4 + ($r - 2));
	}

	private function _setValue(int $k, int $r, int $value): void
	{
		$j = self::index($k, $r);
		$this->buf[$j] = $value;
	}

	private function FV($k)
	{
		return $this->_getValue($k, 0);
	}

	private function RV($k)
	{
		return $this->_getValue($k, 1);
	}

	private function _getValue($k, $r)
	{
		$j = self::index($k, $r);
		if (!array_key_exists($j, $this->buf)) {
			$this->buf[$j] = 0;
		}
		return $this->buf[$j];
	}

	/**
	 *
	 * @param int $aOffset
	 * @param int $n
	 * @param int $bOffset
	 * @param int $m
	 * @param MiddleSnake $ms
	 * @return int
	 * @throws NotFoundException
	 */
	private function _findMiddleSnake(int $aOffset, int $n, int $bOffset, int $m, MiddleSnake $ms): int
	{
		$delta = $n - $m;
		$odd = $delta & 1;
		$mid = ($n + $m) / 2;
		$mid += $odd;

		$this->_setValue(1, 0, 0);
		$this->_setValue($delta - 1, 1, $n);

		for ($d = 0; $d <= $mid; $d++) {
			if ((2 * $d - 1) >= $this->distanceMaximum) {
				return $this->distanceMaximum;
			}

			for ($k = $d; $k >= -$d; $k -= 2) {
				if ($k == -$d || ($k != $d && $this->FV($k - 1) < $this->FV($k + 1))) {
					$x = $this->FV($k + 1);
				} else {
					$x = $this->FV($k - 1) + 1;
				}
				$y = $x - $k;

				$ms->x = $x;
				$ms->y = $y;

				while ($x < $n && $y < $m && $this->left[$aOffset + $x] === $this->right[$bOffset + $y]) {
					$x++;
					$y++;
				}

				$this->_setValue($k, 0, $x);

				if ($odd && $k >= ($delta - ($d - 1)) && $k <= ($delta + ($d - 1))) {
					if ($x >= $this->RV($k)) {
						$ms->u = $x;
						$ms->v = $y;
						return 2 * $d - 1;
					}
				}
			}
			for ($k = $d; $k >= -$d; $k -= 2) {
				$kr = ($n - $m) + $k;

				if ($k == $d || ($k != -$d && $this->RV($kr - 1) < $this->RV($kr + 1))) {
					$x = $this->RV($kr - 1);
				} else {
					$x = $this->RV($kr + 1) - 1;
				}
				$y = $x - $kr;

				$ms->u = $x;
				$ms->v = $y;

				while ($x > 0 && $y > 0 && $this->left[$aOffset + $x - 1] === $this->right[$bOffset + $y - 1]) {
					$x--;
					$y--;
				}

				$this->_setValue($kr, 1, $x);

				if (!$odd && $kr >= -$d && $kr <= $d) {
					if ($x <= $this->FV($kr)) {
						$ms->x = $x;
						$ms->y = $y;
						return 2 * $d;
					}
				}
			}
		}

		throw new NotFoundException('No middle snake found?');
	}

	private function _edit(string $op, int $off, int $len): void
	{
		if ($len === 0) {
			return;
		}
		/*
		 * Add an edit to the SES (or
		 * coalesce if the op is the same)
		 */
		$editCount = count($this->edits);
		if ($editCount) {
			$edit = $this->edits[$editCount - 1];
			if ($edit->op === $op) {
				$edit->len += $len;
				return;
			}
		}
		$edit = new Edit($op, $off, $len);
		$this->edits[] = $edit;
	}

	//	static int
	//	_ses(const void *a, int aoff, int n,
	//	const void *b, int boff, int m,
	//	Diff_Context ctx)
	/**
	 * @param int $aOffset
	 * @param int $n
	 * @param int $bOffset
	 * @param int $m
	 * @return int
	 * @throws NotFoundException
	 */
	private function _ses(int $aOffset, int $n, int $bOffset, int $m): int
	{
		$ms = new MiddleSnake();

		if ($n == 0) {
			$this->_edit(Edit::DIFF_INSERT, $bOffset, $m);
			return $m;
		}

		if ($m == 0) {
			$this->_edit(Edit::DIFF_DELETE, $aOffset, $n);
			return $n;
		}

		/*
		 * Find the middle "snake" around which we
		 * recursively solve the sub-problems.
		 */
		$d = $this->_findMiddleSnake($aOffset, $n, $bOffset, $m, $ms);
		if ($d === -1) {
			return -1;
		}
		if ($d >= $this->distanceMaximum) {
			return $this->distanceMaximum;
		}
		if ($d > 1) {
			if ($this->_ses($aOffset, $ms->x, $bOffset, $ms->y) == -1) {
				return -1;
			}

			$this->_edit(Edit::DIFF_MATCH, $aOffset + $ms->x, $ms->u - $ms->x);

			$aOffset += $ms->u;
			$bOffset += $ms->v;
			$n -= $ms->u;
			$m -= $ms->v;
			if ($this->_ses($aOffset, $n, $bOffset, $m) == -1) {
				return -1;
			}
			return $d;
		}

		$x = $ms->x;
		$u = $ms->u;

		/* There are only 4 base cases when the
		 * edit distance is 1.
		 *
		 * n > m   m > n
		 *
		 *   -       |
		 *    \       \    x != u
		 *     \       \
		 *
		 *   \       \
		 *    \       \    x == u
		 *     -       |
		 */
		if ($m > $n) {
			if ($x == $u) {
				$this->_edit(Edit::DIFF_MATCH, $aOffset, $n);
				$this->_edit(Edit::DIFF_INSERT, $bOffset + ($m - 1), 1);
			} else {
				$this->_edit(Edit::DIFF_INSERT, $bOffset, 1);
				$this->_edit(Edit::DIFF_MATCH, $aOffset, $n);
			}
		} else {
			if ($x == $u) {
				$this->_edit(Edit::DIFF_MATCH, $aOffset, $m);
				$this->_edit(Edit::DIFF_DELETE, $aOffset + ($n - 1), 1);
			} else {
				$this->_edit(Edit::DIFF_DELETE, $aOffset, 1);
				$this->_edit(Edit::DIFF_MATCH, $aOffset + 1, $m);
			}
		}

		return $d;
	}
}
