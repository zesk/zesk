<?php
/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2016 Market Acumen, Inc.
 *
 * http://www.ietf.org/rfc/rfc2445.txt
 * 
 * Based on python-dateutil rrule.py, just like everyone else's version
 * 
 * https://pypi.python.org/pypi/python-dateutil/2.5.3
 */
namespace RRule;

use zesk\str;
use zesk\Timestamp;
use zesk\Exception_Parse;
use zesk\Hookable;

/**
 * 
 */
class Parser extends Hookable {
	protected $options = array(
		"interval" => 1
	);
	public static function factory($code) {
		$rrule = new self();
		return $rrule->parse($code);
	}
	public function parse($code) {
		if (!self::$inited) {
			self::_init();
		}
		if (!begins("RRULE:", $code)) {
			throw new Exception_Parse("No RRULE: prefix in code \"{code}\"", array(
				"code" => $code
			));
		}
		$code = str::unprefix($code, 'RRULE:');
		if (preg_match_all('/([A-Z]+)=([^;]*);/gi', "$code;", $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$term = $match[1];
				$value = $match[2];
				$method = "parse_$term";
				$this->set_option($term, method_exists($this, $method) ? $this->$method($value) : $value);
			}
		}
		return $this;
	}
	
	/**
	 * Convert an integer day of the week into the token
	 * @param integer $dow
	 * @return integer|null
	 */
	private static function _dow_from_string($dow) {
		if (is_array($dow)) {
			foreach ($dow as $k => $v) {
				$dow[$k] = self::_dow_from_string($v);
			}
			return $dow;
		}
		return avalue(self::$dow_flip, $dow, null);
	}
	/**
	 * Convert an integer day of the week into the token
	 * @param integer $dow
	 * @return string|null
	 */
	private static function _dow_to_string($dow) {
		return self::$dow[intval($dow) % 7];
	}
	private function _parse_integer_list($value, $min = null, $max = null) {
		$result = array();
		foreach (explode(",", $value) as $v) {
			$v = intval($v);
			if ($min !== null) {
				$v = max($min, $v);
			}
			if ($max !== null) {
				$v = min($max, $v);
			}
			$result[] = $v;
		}
		$result = array_unique($result);
		sort($result, SORT_NUMERIC);
		return $result;
	}
	private function parse_INTERVAL($value) {
		return intval(abs($value));
	}
	private function parse_COUNT($value) {
		return intval($value);
	}
	private function parse_TZID($value) {
		return strval($value);
	}
	private function parse_UNTIL($value) {
		return new Timestamp(strtotime($value));
	}
	private function parse_WKST($value) {
		return self::_dow_from_string($value);
	}
	private function parse_BYMONTH($value) {
		return self::_parse_integer_list($value, 1, 12);
	}
	private function parse_BYWEEKNO($value) {
		return self::_parse_integer_list($value, -53, 53);
	}
	private function parse_BYYEARDAY($value) {
		return self::_parse_integer_list($value, -366, 366);
	}
	private function parse_BYMONTHDAY($value) {
		return self::_parse_integer_list($value, -31, 31);
	}
	private function parse_BYDAY($value) {
		return explode(",", $value);
	}
	private function parse_BYHOUR($value) {
		return self::_parse_integer_list($value, 0, 23);
	}
	private function parse_BYMINUTE($value) {
		return self::_parse_integer_list($value, 0, 59);
	}
	private function parse_BYSECOND($value) {
		return self::_parse_integer_list($value, 0, 59);
	}
	private function parse_BYSETPOS($value) {
		return self::_parse_integer_list($value, -366, 366);
	}
	public function compute(Timestamp $start = null, Timestamp $end = null) {
	}
}
