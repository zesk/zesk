<?php declare(strict_types=1);
/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 *
 * http://www.ietf.org/rfc/rfc2445.txt
 *
 * Based on python-dateutil, just like everyone else's version
 *
 * https://pypi.python.org/pypi/python-dateutil/2.5.3
 *
 * see module's README.txt for license information.
 */
namespace RRule;

class Weekday {
	/**
	 *
	 * @var integer
	 */
	public $weekday = null;

	/**
	 *
	 * @var integer
	 */
	public $n = null;

	/**
	 *
	 * @param integer $weekday
	 * @param integer $n
	 * @throws \zesk\Exception_Range
	 */
	public function __construct($weekday, $n = null) {
		if ($n === 0) {
			throw new \zesk\Exception_Range("Can't create a weekday with n == 0");
		}
		$this->weekday = $weekday;
		$this->n = $n;
	}

	public function __invoke($n) {
		return $n === $this->n ? $this : new self($this->weekday, $n);
	}

	public function equals(Weekday $other) {
		return $this->weekday === $other->weekday && $this->n === $other->n;
	}

	public function render() {
		$dname = Rule::$dow[$this->weekday];
		$n = $this->n;
		$sign = $n > 0 ? "+" : "";
		return $this->n === null ? $dname : "$dname($sign$n)";
	}
}
