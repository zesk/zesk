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

use zesk\Timestamp;
use zesk\Options;
use zesk\Exception_Syntax;
use zesk\Exception_Range;
use zesk\Exception_Parameter;

/**
 * 
 */
class Rule_Parser extends Options {
	private static $freq_map = array(
		"YEARLY" => Rule::FREQUENCY_YEARLY,
		"MONTHLY" => Rule::FREQUENCY_MONTHLY,
		"WEEKLY" => Rule::FREQUENCY_WEEKLY,
		"DAILY" => Rule::FREQUENCY_DAILY,
		"HOURLY" => Rule::FREQUENCY_HOURLY,
		"MINUTELY" => Rule::FREQUENCY_MINUTELY,
		"SECONDLY" => Rule::FREQUENCY_SECONDLY
	);
	/**
	 * This differs from the Python version in that Monday is 1, not 0.
	 * 
	 * One point for Python.
	 * 
	 * @var array
	 */
	private static $weekday_map = array(
		"SU" => 0,
		"MO" => 1,
		"TU" => 2,
		"WE" => 3,
		"TH" => 4,
		"FR" => 5,
		"SA" => 6
	);
	
	/**
	 * Code parsed
	 * 
	 * @var string
	 */
	protected $code = null;
	/**
	 * 
	 * @var string[]
	 */
	protected $errors = array();
	
	/**
	 * 
	 * @var array
	 */
	protected $options = array(
		"interval" => 1
	);
	public static function factory($code) {
		$rrule = new self();
		return $rrule->parse_rule($code);
	}
	public function dtstart(Timestamp $set = null) {
		return $set ? $this->set_option('dtstart', $set) : $this->option('dtstart');
	}
	public function errors() {
		return $this->errors;
	}
	/**
	 * 
	 */
	public function parse_rule($code) {
		$this->errors = array();
		$matches = null;
		if (preg_match_all('/([A-Z]+)=([^;]*);/i', "$code;", $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$term = $match[1];
				$value = $match[2];
				$method = "parse_$term";
				$converted_value = method_exists($this, $method) ? $this->$method($value) : $value;
				$this->options[strtolower($term)] = $converted_value;
			}
		}
		if (count($this->errors) > 0) {
			throw new Exception_Syntax(implode("\n", $this->errors));
		}
		$this->code = $code;
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
		return avalue(self::$weekday_map, $dow, null);
	}
	private function _parse_integer_list($value, $min = null, $max = null) {
		$result = array();
		foreach (explode(",", $value) as $v) {
			if ($v === "") {
				continue;
			}
			$v = intval($v);
			if ($min !== null) {
				$v = max($min, $v);
			}
			if ($max !== null) {
				$v = min($max, $v);
			}
			$result[$v] = $v;
		}
		$result = array_keys($result);
		sort($result, SORT_NUMERIC);
		return $result;
	}
	private function parse_FREQ($value) {
		if (!array_key_exists($value, self::$freq_map)) {
			$this->errors[] = __(__CLASS__ . ":=Invalid frequency \"{value}\"", compact("value"));
			return null;
		}
		return self::$freq_map[$value];
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
		return Timestamp::factory(rtrim($value, 'Z'), "UTC");
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
		return self::parse_BYWEEKDAY($value);
	}
	private function parse_BYWEEKDAY($value) {
		// Two ways to specify this: +1MO or MO(+1)
		$result = array();
		$value = explode(",", $value);
		foreach ($value as $wday) {
			if (preg_match("#([MTWFS][OUEHRA])\(([-+]?[0-9]+)\)#i", $wday, $matches)) {
				# If it's of the form TH(+1), etc.
				list($ignore, $wday, $n) = $matches;
				$n = intval(trim($n, "+"));
			} else if (preg_match("#([-+]?[0-9]+)?([MTWFS][OUEHRA])#i", $wday, $matches)) {
				# If it's of the form +1MO
				list($ignore, $n, $wday) = $matches;
				if (!empty($n)) {
					$n = intval(trim($n, "+"));
				} else {
					$n = null;
				}
			} else {
				$this->errors[] = __(__CLASS__ . ":=Invalid BYDAY token: {wday}", compact("wday"));
				continue;
			}
			if (!array_key_exists($wday, self::$weekday_map)) {
				$this->errors[] = __(__CLASS__ . ":=Invalid BYDAY token: {wday}", compact("wday"));
				continue;
			}
			$wday = strtoupper($wday);
			$result[] = new Weekday(self::$weekday_map[$wday], $n);
		}
		return $result;
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
	public function rule() {
		try {
			return new Rule($this->options['freq'], $this->options);
		} catch (Exception_Parameter $e) {
			throw new Exception_Syntax("Parameter error with code:\n{code}\nError: {message}", array(
				"code" => $this->code,
				"message" => $e->getMessage()
			));
		} catch (Exception_Range $e) {
			throw new Exception_Syntax("Range error with code:\n{code}\nError: {message}", array(
				"code" => $this->code,
				"message" => $e->getMessage()
			));
		}
	}
}
