<?php

/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */

/**
 * Time of day
 *
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage model
 */
namespace zesk;

class Time extends Temporal {
	/**
	 *
	 * @var string
	 */
	const DEFAULT_FORMAT_STRING = "{hh}:{mm}:{ss}";

	/**
	 * Set up upon load
	 *
	 * @var string
	 */
	private static $default_format_string = self::DEFAULT_FORMAT_STRING;

	/**
	 * 24 hours a day.
	 *
	 * @var integer
	 */
	const hours_per_day = 24;

	/**
	 * Maximum 0-based hour is 23
	 *
	 * @var integer
	 */
	const hour_max = 23;

	/**
	 * Maximum 0-based second is 59
	 *
	 * @var integer
	 */
	const second_max = 59;

	/**
	 * Maximum 0-indexed minute is 59
	 *
	 * @var integer
	 */
	const minute_max = 59;

	/**
	 * Maximum value for seconds from midnight in a day
	 *
	 * @var integer
	 */
	const seconds_max = 86399;

	/**
	 * 60 seconds in a minute
	 *
	 * @var integer
	 */
	const seconds_per_minute = 60;

	/**
	 * 3,600 seconds an hour
	 *
	 * @var integer
	 */
	const seconds_per_hour = 3600;

	/**
	 * 86,400 seconds in a day
	 *
	 * @var integer
	 */
	const seconds_per_day = 86400;

	/**
	 * Integer value of seconds from midnight.
	 *
	 * Valid value range 0 to self::seconds_max
	 *
	 * If null, represents no value assigned, yet.
	 *
	 * @var integer
	 */
	protected $seconds = null;

	/**
	 * Millisecond offset (0-999)
	 *
	 * @var integer
	 */
	protected $milliseconds = null;

	/**
	 * Add global configuration
	 *
	 * @param Kernel $kernel
	 */
	public static function hooks(Application $kernel) {
		$kernel->hooks->add(Hooks::HOOK_CONFIGURED, array(
			__CLASS__,
			"configured",
		));
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application) {
		self::$default_format_string = $application->configuration->path_get(array(
			__CLASS__,
			"format_string",
		), self::DEFAULT_FORMAT_STRING);
	}

	/**
	 * Create a new Time object by calling a static method
	 *
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Time
	 */
	public static function instance($hour = 0, $minute = 0, $second = 0) {
		$tt = new Time();
		$tt->hms($hour, $minute, $second);
		return $tt;
	}

	/**
	 * Construct a new Time object
	 *
	 * @param mixed $value
	 * @see Time::set
	 */
	public function __construct($value = null) {
		$this->set($value);
	}

	/**
	 * Create a Time object
	 *
	 * @param mixed $value
	 * @return Time
	 */
	public static function factory($value = null) {
		return new self($value);
	}

	/**
	 * Create exact replica of this object
	 *
	 * @return \zesk\Time
	 */
	public function duplicate() {
		return clone $this;
	}

	/**
	 * Return a new Time object representing current time of day
	 *
	 * @return Time
	 */
	public static function now() {
		return self::factory('now');
	}

	/**
	 * Set the time object
	 *
	 * @param mixed $value
	 *        	Number of seconds from midnight, a string, null, Time, or Timestamp
	 * @throws Exception_Convert
	 * @return Time
	 */
	public function set($value) {
		if (is_integer($value)) {
			$this->unix_timestamp($value);
			return $this;
		} elseif (empty($value)) {
			$this->set_empty();
			return $this;
		} elseif (is_string($value)) {
			return $this->parse($value);
		} elseif ($value instanceof Time) {
			$this->seconds = $value->seconds;
			$this->milliseconds = $value->milliseconds;
			return $this;
		} elseif ($value instanceof Timestamp) {
			$this->seconds = $value->day_seconds();
			$this->milliseconds = $value->millisecond();
			return $this;
		}

		throw new Exception_Parameter(map("Time::set({0})", array(_dump($value))));
	}

	/**
	 * Is this object empty?
	 *
	 * @return boolean
	 */
	public function is_empty() {
		return $this->seconds === null;
	}

	/**
	 * Set this object as empty
	 *
	 * @return Time
	 */
	public function set_empty() {
		$this->seconds = null;
		return $this;
	}

	/**
	 * Set the time to the current time of day
	 *
	 * @todo Support microseconds
	 * @return Time
	 */
	public function set_now() {
		return $this->unix_timestamp(time());
	}

	/**
	 * Set the time of day to midnight
	 *
	 * @return Time
	 */
	public function midnight() {
		$this->seconds = 0;
		return $this;
	}

	/**
	 * Set the time of day to noon
	 *
	 * @return Time
	 */
	public function noon() {
		$this->seconds = 0;
		return $this->hour(12);
	}

	/**
	 * Set or get the unix timestamp.
	 *
	 * @param integer $set
	 *        	Optional value to set
	 * @throws Exception_Parameter
	 * @return Time|string
	 */
	public function unix_timestamp($set = null) {
		if ($set !== null) {
			if (!is_numeric($set)) {
				throw new Exception_Parameter(map("Time::unix_timestamp({0})", array(_dump($set))));
			}
			list($hours, $minutes, $seconds) = explode(" ", gmdate("G n s", $set)); // getdate doesn't support UTC
			$this->hms(intval($hours), intval($minutes), intval($seconds));
			return $this;
		}
		return $this->seconds;
	}

	/**
	 * Set the hour, minute, and second of the day explicitly
	 *
	 * @param integer $hh
	 * @param integer $mm
	 * @param integer $ss
	 * @throws Exception_Range
	 * @return Time
	 */
	public function hms($hh = 0, $mm = 0, $ss = 0) {
		if (($hh < 0) || ($hh > self::hour_max) || ($mm < 0) || ($mm > self::minute_max) || ($ss < 0) || ($ss > self::second_max)) {
			throw new Exception_Range("Time::hms($hh,$mm,$ss)");
		}
		$this->seconds = ($hh * self::seconds_per_hour) + ($mm * self::seconds_per_minute) + $ss;
		return $this;
	}

	/**
	 *
	 * @todo should this honor locale? Or just be generic, programmer-only version
	 * @return string
	 */
	public function __toString() {
		if ($this->is_empty()) {
			return "";
		}
		$result = $this->format(null, "{hh}:{mm}:{ss}");
		return $result;
	}

	/**
	 * Parse a time and set this object
	 *
	 * @param string $value
	 * @throws Exception_Parameter
	 * @return Time
	 */
	public function parse($value) {
		foreach (array(
			"/([0-9]{1,2}):([0-9]{2}):([0-9]{2})/" => array(
				null,
				"hour",
				"minute",
				"second",
			),
			"/([0-9]{1,2}):([0-9]{2})/" => array(
				null,
				"hour",
				"minute",
			),
		) as $pattern => $assign) {
			if (preg_match($pattern, $value, $matches)) {
				$this->hms(0, 0, 0);
				foreach ($assign as $index => $method) {
					if ($method) {
						$this->$method($matches[$index]);
					}
				}
				return $this;
			}
		}
		$tz = date_default_timezone_get();
		date_default_timezone_set("UTC");
		$ts = strtotime($value, $this->unix_timestamp());
		date_default_timezone_set($tz);
		if ($ts === false || $ts < 0) {
			throw new Exception_Parameter(map("Time::parse({0}): Can't parse", array($value)));
		}
		return $this->unix_timestamp($ts);
	}

	/**
	 * Get/set the hour of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	public function hour($set = null) {
		if ($set !== null) {
			return $this->hms($set, $this->minute(), $this->second());
		}
		return intval($this->seconds / self::seconds_per_hour);
	}

	/**
	 * Get/set the minute of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	public function minute($set = null) {
		if ($set === null) {
			return intval(($this->seconds / self::seconds_per_minute) % self::seconds_per_minute);
		}
		return $this->hms($this->hour(), $set, $this->second());
	}

	/**
	 * Get/set the second of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	public function second($set = null) {
		if ($set === null) {
			return $this->seconds % self::seconds_per_minute;
		}
		return $this->hms($this->hour(), $this->minute(), $set);
	}

	/**
	 * Get/set the second of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	public function seconds($set = null) {
		if ($set === null) {
			return $this->seconds;
		}
		return $this->seconds % self::seconds_per_day;
	}

	/**
	 * Get/set the 12-hour of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	public function hour12($set = null) {
		if ($set === null) {
			$hour = intval($this->seconds / self::seconds_per_hour);
			$hour = $hour % 12;
			if ($hour === 0) {
				$hour = 12;
			}
			return $hour;
		}
		$set = $set % 12;
		// Retains AM/PM
		return $this->hour($set + ($this->hour() < 12 ? 0 : 12));
	}

	/**
	 * Returns whether it's am or pm
	 *
	 * @return string
	 */
	public function ampm() {
		$hour = $this->hour();
		return ($hour < 12) ? "am" : "pm";
	}

	/**
	 * Get number of seconds since midnight
	 *
	 * @return integer
	 */
	public function day_seconds() {
		return $this->seconds;
	}

	/**
	 * Compare one time with another
	 *
	 * $this->compare($value) < 0 analagous to $this < $value
	 * $this->compare($value) > 0 analagous to $this > $value
	 *
	 * @param Time $value
	 * @return integer
	 */
	public function compare(Time $value) {
		if ($this->is_empty()) {
			if (!$value->is_empty()) {
				return -1;
			} else {
				return 0;
			}
		} elseif ($value->is_empty()) {
			return 1;
		}
		$result = $this->seconds - $value->seconds;
		return $result;
	}

	/**
	 * Subtract one time from another
	 *
	 * @param Time $value
	 * @return integer
	 */
	public function subtract(Time $value) {
		return $this->seconds - $value->seconds;
	}

	/**
	 * Add hours, minutes, seconds to a time
	 *
	 * @param integer $hh
	 * @param integer $mm
	 * @param integer $ss
	 * @param integer $remain
	 *        	Returned remainder of addition
	 * @return Time
	 */
	public function add($hh = 0, $mm = 0, $ss = 0, &$remain = null) {
		$newValue = $this->seconds + $ss + ($hh * self::seconds_per_hour) + ($mm * self::seconds_per_minute);
		if ($newValue < 0) {
			$remain = intval(($newValue - (self::seconds_per_day - 1)) / self::seconds_per_day);
			$newValue = abs((self::seconds_per_day + $newValue) % self::seconds_per_day);
		} else {
			$remain = intval($newValue / self::seconds_per_day);
			$newValue = $newValue % self::seconds_per_day;
		}
		$this->seconds = $newValue;
		return $this;
	}

	/**
	 * Returns an array of token values (h,m,s,hh,mm,ss and values for this object)
	 *
	 * @return array
	 * @see Time::format
	 */
	public function formatting(Locale $locale = null, array $options = array()) {
		$x = array();
		$x['h'] = $this->hour();
		$x['12h'] = $this->hour12();
		$x['m'] = $this->minute();
		$x['s'] = $this->second();
		foreach ($x as $k => $v) {
			$x[$k . substr($k, -1)] = StringTools::zero_pad($v);
		}
		$x['day_seconds'] = $this->seconds;
		$ampm = $this->ampm();
		if ($locale) {
			$x['ampm'] = $locale("Time:=$ampm");
			$ampm = strtoupper($ampm);
			$x['AMPM'] = $locale("Time:=$ampm");
		}
		return $x;
	}

	/**
	 * Format a time string
	 *
	 * @param string $format_string
	 *        	Formatting string
	 * @param string $locale
	 *        	Optional locale string
	 * @return string
	 */
	public function format(Locale $locale = null, $format_string = null, array $options = array()) {
		if ($format_string === null) {
			$format_string = self::$default_format_string;
		}
		$x = $this->formatting($locale, $options);
		return map($format_string, $x);
	}

	/**
	 * Format HH${sep}MM${sep}SS
	 *
	 * @param string $sep
	 * @return string
	 */
	private function _hms_format($sep = ":") {
		return StringTools::zero_pad($this->hour()) . $sep . StringTools::zero_pad($this->minute()) . $sep . StringTools::zero_pad($this->second());
	}

	/**
	 * Format for SQL
	 *
	 * @return string
	 */
	public function sql() {
		return $this->_hms_format();
	}

	/**
	 * Add a unit to a time
	 *
	 * As of 2017-12-16, Zesk 0.14.1, no longer supports legacy calling (units,n_units)
	 *
	 * @param string $units
	 *        	Unit to add: "millisecond", "second", "minute", "hour"
	 * @param integer $n_units
	 *        	Number to add
	 * @throws Exception_Parameter
	 * @return Time
	 */
	public function add_unit($n_units = 1, $units = self::UNIT_SECOND) {
		if (!is_numeric($n_units)) {
			throw new Exception_Parameter("\$n_units must be numeric {type} {value}", array(
				"type" => type($n_units),
				"value" => $n_units,
			));
		}
		switch ($units) {
			case self::UNIT_MILLISECOND:
				return $this->add(0, 0, round($n_units / 1000));
			case self::UNIT_SECOND:
				return $this->add(0, 0, $n_units);
			case self::UNIT_MINUTE:
				return $this->add(0, $n_units);
			case self::UNIT_HOUR:
				return $this->add($n_units);
			default:
				throw new Exception_Parameter("{method)({n_units}, {units}): Invalid unit", array(
					"method" => __METHOD__,
					"n_units" => $n_units,
					"units" => $units,
				));
		}
	}

	/**
	 * Take the results of PHP getdate and set the Time
	 *
	 * @param array $darr
	 * @return Time
	 */
	private function _set_date(array $darr) {
		return $this->hms($darr["hours"], $darr["minutes"], $darr["seconds"]);
	}

	/**
	 *
	 * @param Time $a
	 * @param Time $b
	 * @return number
	 */
	public static function sort_callback(Time $a, Time $b) {
		$delta = $a->seconds - $b->seconds;
		if ($delta < 0) {
			return -1;
		} elseif ($delta === 0) {
			return 0;
		} else {
			return 1;
		}
	}
}
