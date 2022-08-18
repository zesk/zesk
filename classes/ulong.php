<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * @requires PHP 5
 * Created on Mon Apr 06 22:59:37 EDT 2009 22:59:37
 */
namespace zesk;

/**
 * Because PHP only supports signed integers, this class allows you to operate on unsigned longs
 * and they behave correctly.
 *
 * Do not use this for anything complex, as math is really slow with objects.
 *
 * However, in a pinch, it should behave correctly and wrap around without the annoying overflow
 * and double issues of PHP.
 */
class ulong {
	public $short0;

	public $short1;

	public const maxshort = 0xFFFF;

	public const maxlong = 0xFFFFFFFF;

	public function __construct($x = 0) {
		$this->set($x);
	}

	public static function to_ulong($x, $copy = false) {
		if (is_numeric($x)) {
			return new ulong($x);
		}
		if ($x instanceof ulong) {
			return $copy ? clone $x : $x;
		}
		die(gettype($x) . ' ' . $x::class . print_r(debug_backtrace()));
	}

	public function get() {
		return $this->short1 * (self::maxshort + 1) + $this->short0;
	}

	public function set($x) {
		if ($x instanceof ulong) {
			$this->short0 = $x->short0;
			$this->short1 = $x->short1;
		} else {
			$this->short0 = intval(($x >> 0) & self::maxshort);
			$this->short1 = intval(($x >> 16) & self::maxshort);
		}
		return $this;
	}

	public function byte($n) {
		switch ($n) {
			case 0:
				return $this->short0 & 0xFF;
			case 1:
				return ($this->short0 >> 8) & 0xFF;
			case 2:
				return $this->short1 & 0xFF;
			case 3:
				return ($this->short1 >> 8) & 0xFF;
			default:
				return 0;
		}
	}

	public function add($x) {
		$x = self::to_ulong($x);
		$this->short0 += $x->short0;
		$rem = ($this->short0 >> 16) && self::maxshort;
		$this->short0 = $this->short0 & self::maxshort;
		$this->short1 += $x->short1 + $rem;
		$this->short1 = $this->short1 & self::maxshort;
		return $this;
	}

	public function sub($x) {
		$x = self::to_ulong($x);
		$s0 = $this->short0 - $x->short0;
		$borrow = 0;
		if ($s0 < 0) {
			$s0 += (self::maxshort + 1);
			$borrow = 1;
		}
		$s1 = $this->short1 - $x->short1 - $borrow;
		if ($s1 < 0) {
			$s1 += (self::maxshort + 1);
		}
		$this->short0 = $s0 & self::maxshort;
		$this->short1 = $s1 & self::maxshort;
		return $this;
	}

	public function bit_and($x) {
		$x = self::to_ulong($x);
		$this->short0 = $this->short0 & $x->short0;
		$this->short1 = $this->short1 & $x->short1;
		return $this;
	}

	public function bit_or($x) {
		$x = self::to_ulong($x);
		$this->short0 |= $x->short0;
		$this->short1 |= $x->short1;
		return $this;
	}

	public function bit_xor($x) {
		$x = self::to_ulong($x);
		$this->short0 ^= $x->short0;
		$this->short1 ^= $x->short1;
		return $this;
	}

	public function lshift($n) {
		$s1 = $this->short1 << $n;
		$s0 = $this->short0 << $n;
		$this->short1 = ($s1 & self::maxshort) | (($s0 >> 16) & self::maxshort);
		$this->short0 = $s0 & self::maxshort;
		//echo "$this\n";
		return $this;
	}

	public function rshift($n) {
		$s1 = $this->short1;
		$this->short0 = ($s1 << 16 - $n) & self::maxshort | ($this->short0 >> $n);
		$this->short1 = ($s1 >> $n) & self::maxshort;
		return $this;
	}

	public function __tostring() {
		return '' . $this->get();
	}
}
