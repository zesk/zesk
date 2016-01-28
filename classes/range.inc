<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/range.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 12:35:12 EDT 2008
 */
class range {

	/**
	 * Given two pairs of sequential items, find out if they overlap at all
	 *
	 *	Possible results are:
	 *
	 *		a0--a1  b0--b1         false
	 *
	 *		b0--b1  a0--a1         false
	 *
	 *		a0------a1             array(a0,b1)
	 *		    b0------b1
	 *
	 *		a0----------a1         array(a0,a1)
	 *		    b0--b1
	 *
	 *		    a0------a1         array(b0,a1)
	 *		b0------b1
	 *
	 *		    a0--a1             array(b0,b1)
	 *		b0----------b1
	 *
	 * Extra 4 parameter are associated indexes for values which are returned in the result array as the 2nd pair
	 */
	public static function overlap($a0, $a1, $b0, $b1, $a0i = false, $a1i = false, $b0i = false, $b1i = false) {
		assert($a0 < $a1);
		assert($b0 < $b1);

		if (($a1 < $b0) || ($a0 > $b1)) {
			return false;
		}
		if ($a0 <= $b0) {
			if ($a1 <= $b1) {
				return array(
					$a0,
					$b1,
					$a0i,
					$b1i
				);
			} else {
				return array(
					$a0,
					$a1,
					$a0i,
					$a1i
				);
			}
		}
		// $b0 < $a0
		if ($a1 <= $b1) {
			return array(
				$b0,
				$b1,
				$b0i,
				$b1i
			);
		} else {
			return array(
				$b0,
				$a1,
				$a0i,
				$a1i
			);
		}
	}

	/**
	 * Given two pairs of sequential items, subtract a range
	 *
	 *	Possible results are:
	 *
	 *		a0--a1  b0--b1         array(a0,a1)
	 *
	 *		b0--b1  a0--a1         array(a0,a1)
	 *
	 *		a0------a1             array(a0,b0)
	 *		    b0------b1
	 *
	 *		a0----------a1         array(a0,b0,b1,a1)
	 *		    b0--b1
	 *
	 *		    a0------a1         array(b1,a1)
	 *		b0------b1
	 *
	 *		    a0--a1             false
	 *		b0----------b1
	 *
	 * @return array
	 */
	public static function subtract($a0, $a1, $b0, $b1, $a0i = false, $a1i = false, $b0i = false, $b1i = false) {
		assert($a0 <= $a1);
		assert($b0 <= $b1);

		if (($a1 < $b0) || ($a0 > $b1)) {
			return array(
				$a0,
				$a1,
				$a0i,
				$a1i
			);
		}
		if ($a0 < $b0) {
			if ($a1 <= $b1) {
				return array(
					$a0,
					$b0,
					$a0i,
					$b0i
				);
			} else {
				return array(
					$a0,
					$b0,
					$b1,
					$a1,
					$a0i,
					$b0i,
					$b1i,
					$a1i
				);
			}
		} else if ($a0 == $b0) {
			if ($a1 <= $b1) {
				return false;
			} else {
				return array(
					$b1,
					$a1,
					$b1i,
					$a1i
				);
			}
		} else {
			assert($b0 < $a0);
			// $b0 < $a0
			if ($a1 <= $b1) {
				return false;
			} else {
				return array(
					$b1,
					$a1,
					$b1i,
					$a1i
				);
			}
		}
	}
}
