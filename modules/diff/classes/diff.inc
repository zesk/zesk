<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/diff/classes/diff.inc $
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * Created on Mon,Aug 1, 11 at 5:27 PM
 */
namespace zesk;

/*
 * diff - compute a shortest edit script (SES) given two sequences
 * Copyright (c) 2004 Michael B. Allen <mba2000 ioplex.com>
 *
 * Ported to PHP by Kent M. Davidson
 * kent@marketacumen.com
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
class Diff {
	
	/**
	 * Track state of matching
	 *
	 * @var array of integer
	 */
	private $buf;
	
	/**
	 * Maximum
	 *
	 * @var unknown_type
	 */
	private $dmax;
	
	/**
	 * List of edits to make A match B
	 *
	 * @var array of Diff_Edit
	 */
	private $edits;
	
	/**
	 * List of edits to make A match B, excluding Diff_Edit::DIFF_MATCH
	 *
	 * @var array of Diff_Edit
	 */
	private $diffs;
	
	/**
	 * Compare A
	 *
	 * @var array
	 */
	private $a;
	
	/**
	 * Compare B
	 *
	 * @var array
	 */
	private $b;
	
	/**
	 * Create a diff object and compute the shortest edit sequence to make $a like $b
	 *
	 * @param array $a
	 * @param array $b
	 * @param integer $dmax Maximum snake sequence? (Not sure what this is)
	 * @return Diff
	 */
	function __construct(array $a, array $b) {
		$this->buf = array();
		$this->edits = array();
		$this->diffs = null;
		
		$this->a = $a;
		$this->b = $b;
		
		$n = count($a);
		$m = count($b);
		
		$this->dmax = $n + $m + 2;
		/*
		 * The _ses function assumes the SES will begin or end with a delete
		 * or insert. The following will insure this is true by eating any
		 * beginning matches. This is also a quick to process sequences
		 * that match entirely.
		 */
		$x = 0;
		while ($x < $n && $x < $m && $this->a[$x] === $this->b[$x]) {
			$x++;
		}
		$y = $x;
		
		$this->_edit(Diff_Edit::DIFF_MATCH, 0, $x);
		
		$d = $this->_ses($x, $n - $x, $y, $m - $y);
		
		$this->buf = null;
		
		return $d;
	}
	public function edits() {
		return $this->edits;
	}
	public function is_identical() {
		return (count($this->diffs()) === 0);
	}
	public function diffs() {
		if (is_array($this->diffs)) {
			return $this->diffs;
		}
		$diffs = array();
		foreach ($this->edits as $edit) {
			if ($edit->op !== Diff_Edit::DIFF_MATCH) {
				$diffs[] = $edit;
			}
		}
		$this->diffs = $diffs;
		return $diffs;
	}
	private static function index($k, $r) {
		$j = ($k <= 0) ? (-$k * 4 + $r) : ($k * 4 + ($r - 2));
		return $j;
	}
	private function _setv($k, $r, $val) {
		$j = self::index($k, $r);
		$this->buf[$j] = $val;
	}
	private function FV($k) {
		return $this->_v($k, 0);
	}
	private function RV($k) {
		return $this->_v($k, 1);
	}
	private function _v($k, $r) {
		$j = self::index($k, $r);
		if (!array_key_exists($j, $this->buf)) {
			$this->buf[$j] = 0;
		}
		return $this->buf[$j];
	}
	private function _find_middle_snake($aoff, $n, $boff, $m, Diff_MiddleSnake $ms) {
		$delta = $n - $m;
		$odd = $delta & 1;
		$mid = ($n + $m) / 2;
		$mid += $odd;
		
		$this->_setv(1, 0, 0);
		$this->_setv($delta - 1, 1, $n);
		
		for ($d = 0; $d <= $mid; $d++) {
			if ((2 * $d - 1) >= $this->dmax) {
				return $this->dmax;
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
				
				while ($x < $n && $y < $m && $this->a[$aoff + $x] === $this->b[$boff + $y]) {
					$x++;
					$y++;
				}
				
				$this->_setv($k, 0, $x);
				
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
				
				while ($x > 0 && $y > 0 && $this->a[$aoff + $x - 1] === $this->b[$boff + $y - 1]) {
					$x--;
					$y--;
				}
				
				$this->_setv($kr, 1, $x);
				
				if (!$odd && $kr >= -$d && $kr <= $d) {
					if ($x <= $this->FV($kr)) {
						$ms->x = $x;
						$ms->y = $y;
						return 2 * $d;
					}
				}
			}
		}
		
		throw new Exception("No middle snake found?");
		
		return -1;
	}
	private function _edit($op, $off, $len) {
		if ($len == 0) {
			return;
		}
		/*
		 * Add an edit to the SES (or
		 * coalesce if the op is the same)
		 */
		$nedits = count($this->edits);
		if ($nedits) {
			$edit = $this->edits[$nedits - 1];
			if ($edit->op === $op) {
				$edit->len += $len;
				return;
			}
		}
		$edit = new Diff_Edit($op, $off, $len);
		$this->edits[] = $edit;
	}
	
	//	static int
	//	_ses(const void *a, int aoff, int n,
	//	const void *b, int boff, int m,
	//	Diff_Context ctx)
	private function _ses($aoff, $n, $boff, $m) {
		$ms = new Diff_MiddleSnake();
		
		if ($n == 0) {
			$this->_edit(Diff_Edit::DIFF_INSERT, $boff, $m);
			$d = $m;
			return $d;
		}
		
		if ($m == 0) {
			$this->_edit(Diff_Edit::DIFF_DELETE, $aoff, $n);
			$d = $n;
			return $d;
		}
		
		/*
		 * Find the middle "snake" around which we
		 * recursively solve the sub-problems.
		 */
		$d = $this->_find_middle_snake($aoff, $n, $boff, $m, $ms);
		if ($d == -1) {
			return -1;
		}
		if ($d >= $this->dmax) {
			return $this->dmax;
		}
		if ($d > 1) {
			if ($this->_ses($aoff, $ms->x, $boff, $ms->y) == -1) {
				return -1;
			}
			
			$this->_edit(Diff_Edit::DIFF_MATCH, $aoff + $ms->x, $ms->u - $ms->x);
			
			$aoff += $ms->u;
			$boff += $ms->v;
			$n -= $ms->u;
			$m -= $ms->v;
			if ($this->_ses($aoff, $n, $boff, $m) == -1) {
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
				$this->_edit(Diff_Edit::DIFF_MATCH, $aoff, $n);
				$this->_edit(Diff_Edit::DIFF_INSERT, $boff + ($m - 1), 1);
			} else {
				$this->_edit(Diff_Edit::DIFF_INSERT, $boff, 1);
				$this->_edit(Diff_Edit::DIFF_MATCH, $aoff, $n);
			}
		} else {
			if ($x == $u) {
				$this->_edit(Diff_Edit::DIFF_MATCH, $aoff, $m);
				$this->_edit(Diff_Edit::DIFF_DELETE, $aoff + ($n - 1), 1);
			} else {
				$this->_edit(Diff_Edit::DIFF_DELETE, $aoff, 1);
				$this->_edit(Diff_Edit::DIFF_MATCH, $aoff + 1, $m);
			}
		}
		
		return $d;
	}
}

