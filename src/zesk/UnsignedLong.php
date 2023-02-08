<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @requires PHP 5
 * Created on Mon Apr 06 22:59:37 EDT 2009 22:59:37
 */
namespace zesk;

/**
 * Because PHP only supports signed integers, this class allows you to operate on unsigned longs,
 * and they behave correctly.
 *
 * Do not use this for anything complex, as math is really slow with objects.
 *
 * However, in a pinch, it should behave correctly and wrap around without the annoying overflow
 * and double issues of PHP.
 */
class UnsignedLong {
	public int $short0;

	public int $short1;

	public const MAXIMUM_SHORT = 0xFFFF;

	public function __construct(int $x = 0) {
		$this->set($x);
	}

	public static function factory(self|int $x, $copy = false): self {
		return ($x instanceof self) ? ($copy ? clone $x : $x) : new UnsignedLong($x);
	}

	public function get(): int {
		return $this->short1 * (self::MAXIMUM_SHORT + 1) + $this->short0;
	}

	public function set(int|self $x): self {
		if ($x instanceof self) {
			$this->short0 = $x->short0;
			$this->short1 = $x->short1;
		} else {
			$this->short0 = ($x >> 0) & self::MAXIMUM_SHORT;
			$this->short1 = ($x >> 16) & self::MAXIMUM_SHORT;
		}
		return $this;
	}

	public function byte(int $n): int {
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

	public function add(int|UnsignedLong $x): self {
		$x = self::factory($x);
		$this->short0 += $x->short0;
		$rem = ($this->short0 >> 16) && self::MAXIMUM_SHORT;
		$this->short0 = $this->short0 & self::MAXIMUM_SHORT;
		$this->short1 += $x->short1 + $rem;
		$this->short1 = $this->short1 & self::MAXIMUM_SHORT;
		return $this;
	}

	public function sub(int|UnsignedLong $x): self {
		$x = self::factory($x);
		$s0 = $this->short0 - $x->short0;
		$borrow = 0;
		if ($s0 < 0) {
			$s0 += (self::MAXIMUM_SHORT + 1);
			$borrow = 1;
		}
		$s1 = $this->short1 - $x->short1 - $borrow;
		if ($s1 < 0) {
			$s1 += (self::MAXIMUM_SHORT + 1);
		}
		$this->short0 = $s0 & self::MAXIMUM_SHORT;
		$this->short1 = $s1 & self::MAXIMUM_SHORT;
		return $this;
	}

	public function bit_and(int|UnsignedLong $x): self {
		$x = self::factory($x);
		$this->short0 = $this->short0 & $x->short0;
		$this->short1 = $this->short1 & $x->short1;
		return $this;
	}

	public function bit_or(int|UnsignedLong $x): self {
		$x = self::factory($x);
		$this->short0 |= $x->short0;
		$this->short1 |= $x->short1;
		return $this;
	}

	public function bit_xor(int|UnsignedLong $x): self {
		$x = self::factory($x);
		$this->short0 ^= $x->short0;
		$this->short1 ^= $x->short1;
		return $this;
	}

	public function lshift(int $n): self {
		$s1 = $this->short1 << $n;
		$s0 = $this->short0 << $n;
		$this->short1 = ($s1 & self::MAXIMUM_SHORT) | (($s0 >> 16) & self::MAXIMUM_SHORT);
		$this->short0 = $s0 & self::MAXIMUM_SHORT;
		//echo "$this\n";
		return $this;
	}

	public function rshift(int $n): self {
		$s1 = $this->short1;
		$this->short0 = ($s1 << 16 - $n) & self::MAXIMUM_SHORT | ($this->short0 >> $n);
		$this->short1 = ($s1 >> $n) & self::MAXIMUM_SHORT;
		return $this;
	}

	public function __toString(): string {
		return '' . $this->get();
	}
}
