<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/time.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2015, Market Acumen, Inc.
 */

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage model
 * @desc Time of day
 */
class Time extends Temporal {

	const hours_per_day = 24;

	const hour_max = 23;

	const second_max = 59;

	const minute_max = 59;

	const seconds_max = 86399;

	const seconds_per_minute = 60;

	const seconds_per_hour = 3600;

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
	 * Construct a new Time object
	 *
	 * @param mixed $value
	 * @see Time::set
	 */
	function __construct($value = null) {
		$this->set($value);
	}

	/**
	 * Create a Time object
	 * @param mixed $value
	 * @return Time
	 */
	public static function factory($value = null) {
		return new Time($value);
	}

	public function duplicate() {
		return clone $this;
	}

	/**
	 * Create a new Time object by calling a static method
	 *
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @todo semantics of "instance" here are incorrect, where other places instance means singleton. Deprecate this usage
	 * @deprecated 2015-04-27
	 * @return Time
	 */
	public static function instance($hour = 0, $minute = 0, $second = 0) {
		$tt = new Time();
		$tt->hms($hour, $minute, $second);
		return $tt;
	}

	/**
	 * Return a new Time object representing current time of day
	 * @return Time
	 */
	public static function now() {
		return Time::factory('now');
	}

	/**
	 * Set the time object
	 *
	 * @param mixed $value Number of seconds from midnight, a string, null, Time, or Timestamp
	 * @throws Exception_Convert
	 * @return Time
	 */
	function set($value) {
		if (is_integer($value)) {
			$this->timestamp($value);
			return $this;
		} else if (empty($value)) {
			$this->set_empty();
			return $this;
		} else if (is_string($value)) {
			return $this->parse($value);
		} else if ($value instanceof Time) {
			$this->seconds = $value->seconds;
			$this->milliseconds = $value->milliseconds;
			return $this;
		} else if ($value instanceof Timestamp) {
			$this->seconds = $value->day_seconds();
			$this->milliseconds = $value->milliseconds();
			return $this;
		}
		throw new Exception_Parameter(__("Time::set({0})", _dump($value)));
	}

	/**
	 * Is this object empty?
	 *
	 * @return boolean
	 */
	function is_empty() {
		return $this->seconds === null;
	}

	/**
	 * Set this object as empty
	 *
	 * @return Time
	 */
	function set_empty() {
		$this->seconds = null;
		return $this;
	}

	/**
	 * Set the time to the current time of day
	 *
	 * @return Time
	 */
	function set_now() {
		return $this->unix_timestamp(time());
	}

	/**
	 * Set the time of day to midnight
	 *
	 * @return Time
	 */
	function midnight() {
		$this->seconds = 0;
		return $this;
	}

	/**
	 * Set the time of day to noon
	 *
	 * @return Time
	 */
	function noon() {
		$this->seconds = 0;
		return $this->hour(12);
	}

	/**
	 * Set or get the unix timestamp.
	 *
	 * @param integer $set Optional value to set
	 * @throws Exception_Parameter
	 * @return Time|string
	 */
	function unix_timestamp($set = null) {
		if ($set !== null) {
			if (!is_numeric($set)) {
				throw new Exception_Parameter(__("Time::unix_timestamp({0})", _dump($set)));
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
	function hms($hh = 0, $mm = 0, $ss = 0) {
		if (($hh < 0) || ($hh > self::hour_max) || ($mm < 0) || ($mm > self::minute_max) || ($ss < 0) || ($ss > self::second_max)) {
			throw new Exception_Range("Time::hms($hh,$mm,$ss)");
		}
		$this->seconds = ($hh * self::seconds_per_hour) + ($mm * self::seconds_per_minute) + $ss;
		return $this;
	}

	/**
	 * @todo should this honor locale? Or just be generic, programmer-only version
	 * @return string
	 */
	function __toString() {
		if ($this->is_empty()) {
			return "";
		}
		$result = self::format("{hh}:{mm}:{ss}");
		return $result;
	}

	/**
	 * Parse a time and set this object
	 *
	 * @param string $value
	 * @throws Exception_Parameter
	 * @return Time
	 */
	function parse($value) {
		$ts = strtotime($value, $this->unix_timestamp());
		if ($ts === false || $ts < 0) {
			throw new Exception_Parameter(__("Time::parse({0}): Can't parse", $value));
		}
		return $this->unix_timestamp($ts);
	}

	/**
	 * Get/set the hour of the day
	 *
	 * @param integer $set
	 * @return Time|number
	 */
	function hour($set = null) {
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
	function minute($set = null) {
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
	function second($set = null) {
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
	function seconds($set = null) {
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
	function hour12($set = null) {
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
	 * @return string
	 */
	function ampm() {
		$hour = $this->hour();
		return ($hour < 12) ? "am" : "pm";
	}

	/**
	 * Get number of seconds since midnight
	 * @return integer
	 */
	function day_seconds() {
		return $this->seconds;
	}

	/**
	 * Compare one time with another
	 *
	 * $this->compare($value) < 0		analagous to		$this < $value
	 * $this->compare($value) > 0		analagous to		$this > $value
	 *
	 * @param Time $value
	 * @return integer
	 */
	function compare(Time $value) {
		if ($this->is_empty()) {
			if (!$value->is_empty()) {
				return -1;
			} else {
				return 0;
			}
		} else if ($value->is_empty()) {
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
	function subtract(Time $value) {
		return $this->seconds - $value->seconds;
	}

	/**
	 * Add hours, minutes, seconds to a time
	 *
	 * @param integer $hh
	 * @param integer $mm
	 * @param integer $ss
	 * @param integer $remain Returned remainder of addition
	 * @return Time
	 */
	function add($hh = 0, $mm = 0, $ss = 0, &$remain = null) {
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
	function formatting($locale = null) {
		$x = array();
		$x['h'] = $this->hour();
		$x['12h'] = $this->hour12();
		$x['m'] = $this->minute();
		$x['s'] = $this->second();
		foreach ($x as $k => $v) {
			$x[$k . substr($k, -1)] = str::zero_pad($v);
		}
		$x['day_seconds'] = $this->seconds;
		$ampm = $this->ampm();
		$x['ampm'] = zesk\Locale::translate("Time:=$ampm", $locale);
		$ampm = strtoupper($ampm);
		$x['AMPM'] = zesk\Locale::translate("Time:=$ampm", $locale);
		return $x;
	}

	/**
	 * Format a time string
	 *
	 * @param string $format_string Formatting string
	 * @param string $locale Optional locale string
	 * @return string
	 */
	function format($format_string = null, $locale = null) {
		if ($format_string === null) {
			$format_string = zesk::get("Time::format_string", "{hh}:{mm}:{ss}");
		}
		$x = $this->formatting($locale);
		return map($format_string, $x);
	}

	/**
	 * Add a unit to a time
	 *
	 * @param string $unit Unit to add: "millisecond", "second", "minute", "hour"
	 * @param integer $n Number to add
	 * @throws Exception_Parameter
	 * @return Time
	 */
	function add_unit($unit = "second", $n = 1) {
		switch ($unit) {
			case "millisecond":
				return $this->add(0, 0, round($n / 1000));
			case "second":
				return $this->add(0, 0, $n);
			case "minute":
				return $this->add(0, $n);
			case "hour":
				return $this->add($n);
			default:
				throw new Exception_Parameter("Time::addUnit($n, $unit): Bad unit");
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
}

