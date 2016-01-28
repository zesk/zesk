<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/timestamp.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

/**
 * Timestamp class is similar to PHP DateTime
 * Used to be called DateTime, and ZDateTime.
 * Timestamp appears to be free, for now as a name and it sounds nice.
 *
 * Namespaces would solve everthing, this class pre-dates namespace support in PHP.
 *
 * Changed in 2014-02-26 to inherit from DateTime to deal with pesky timezone issues.
 * Changed in 2015-04-27 to inherit from Temporal to support universal formatting
 *
 * @author kent
 */
class Timestamp extends Temporal {

	/**
	 *
	 * @var DateTime
	 */
	protected $datetime = null;
	/**
	 *
	 * @var DateTimeZone
	 */
	protected $tz = null;

	/**
	 *
	 * @var integer
	 */
	protected $msec = 0;

	/**
	 * Internal year format - do not use
	 *
	 * @var string
	 */
	const format_year = 'Y';
	const format_month = 'n';
	const format_day = 'j';
	const format_hour = 'G';
	const format_minute = 'i';
	const format_second = 's';
	const format_weekday = 'w';
	const format_yearday = 'z';

	/**
	 * Default __toString format
	 *
	 * @var string
	 */
	const default_format = "{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}";

	public static function timezone_utc() {
		static $utc = null;
		if (!$utc) {
			$utc = new DateTimeZone('UTC');
		}
		return $utc;
	}

	public static function timezone_local() {
		static $locals = array();
		$tz = zesk::get('Timestamp::timezone_local', date_default_timezone_get());
		if (!array_key_exists($tz, $locals)) {
			$locals[$tz] = new DateTimeZone($tz);
		}
		return $locals[$tz];
	}

	/**
	 * Construct a new Timestamp consisting of a Date and a Time
	 *
	 * @param mixed $value
	 */
	function __construct($value = null, DateTimeZone $timezone = null) {
		$this->tz = $timezone === null ? self::timezone_local() : $timezone;
		$this->msec = null;
		if ($value !== null && $value !== '0000-00-00 00:00:00' && $value !== '0000-00-00') {
			$this->datetime = new DateTime(null, $this->tz);
			$this->set($value);
		}
	}

	/**
	 *
	 */
	function __clone() {
		if ($this->datetime) {
			$this->datetime = clone $this->datetime;
		}
	}

	/**
	 * Create a duplicate object
	 *
	 * @return Timestamp
	 */
	function duplicate() {
		return clone $this;
	}

	/**
	 * Set/get time zone
	 *
	 * @param string $mixed
	 * @return DateTimeZone|Timestamp
	 */
	function time_zone($mixed = null) {
		if ($mixed === null) {
			return $this->tz;
		}
		if (!$mixed instanceof DateTimeZone) {
			$mixed = new DateTimeZone($mixed);
		}
		$this->tz = $mixed;
		$this->datetime->setTimezone($mixed);
		return $this;
	}
	/**
	 * Create a new Timestamp with a single value.
	 * Syntactic sugar, for example:
	 * Timestamp::factory($value)->format("{MMM} {DDD}");
	 *
	 * @param mixed $value
	 * @return Timestamp
	 */
	public static function factory($value = null, $timezone = null) {
		if (!$timezone instanceof DateTimeZone) {
			$timezone = empty($timezone) ? null : new DateTimeZone($timezone);
		}
		return new Timestamp($value, $timezone);
	}

	/**
	 * Return new Timestamp date time representing now
	 *
	 * @return Timestamp
	 */
	public static function now($timezone = null) {
		return self::factory("now", $timezone);
	}
	/**
	 * Just prefer utc as an acronym.
	 * Returns UTC Timestamp set to current time.
	 *
	 * @return Timestamp
	 */
	public static function utc_now() {
		return self::factory("now", self::timezone_utc());
	}
	function set_now() {
		$this->unix_timestamp(time());
		return $this;
	}

	/**
	 * Set/get the date component of this Timestamp
	 *
	 * @param Date $date
	 * @return Date date portion of Timestamp
	 */
	function date(Date $date = null) {
		if ($date === null) {
			return Date::instance($this->year(), $this->month(), $this->day());
		}
		$this->ymd($date->year(), $date->month(), $date->day());
		return $this;
	}

	/**
	 * Set/get the time component of this Timestamp
	 *
	 * @param Time $time
	 * @return Time Timestamp
	 */
	function time(Time $time = null) {
		if ($time === null) {
			return Time::instance($this->hour(), $this->minute(), $this->second());
		}
		$this->setTime($time->hour(), $time->minute(), $time->second());
		return $this;
	}

	/**
	 * Set the integer value of this Timestamp
	 *
	 * @see Timestamp::unix_timestamp()
	 * @param integer $set
	 *        	Value to set
	 * @return Timestamp integer
	 */
	function integer($set = null) {
		return $this->unix_timestamp($set);
	}

	/**
	 * Check if this object is empty, or unset
	 *
	 * @return boolean
	 */
	function is_empty() {
		return $this->datetime === null;
	}

	/**
	 * Set this Timetamp to empty
	 *
	 * @return Timestamp
	 */
	function set_empty() {
		$this->datetime = null;
		return $this;
	}

	/**
	 * Set the Timestamp with a variety of formats
	 *
	 * @param mixed $value
	 *        	null, string, integer, Date, Time, Timestamp, or object which returns a date
	 *        	string when
	 *        	converted to string
	 * @throws Exception_Convert
	 * @return Timestamp
	 */
	function set($value) {
		if (empty($value)) {
			$this->set_empty();
			return $this;
		}
		if (is_string($value)) {
			return $this->parse($value);
		}
		if (is_numeric($value)) {
			return $this->unix_timestamp($value);
		}
		if (!is_object($value)) {
			throw new Exception_Convert("Timestamp::set(" . strval($value) . ")");
		}
		if ($value instanceof Date) {
			$this->date($value);
			return $this;
		}
		if ($value instanceof Time) {
			$this->time($value);
			return $this;
		}
		if ($value instanceof Timestamp) {
			return $this->unix_timestamp($value->unix_timestamp());
		}
		return $this->set(strval($value));
	}

	/**
	 * Convert to a standard string, suitable for use in databases and for string comparisons
	 *
	 * @return string
	 */
	function __toString() {
		if ($this->is_empty()) {
			return "";
		}
		return $this->format();
	}

	/**
	 * Require object
	 *
	 * @return DateTime
	 */
	private function _datetime() {
		if ($this->datetime === null) {
			$this->datetime = new DateTime("now", $this->tz);
		}
		return $this->datetime;
	}
	function unix_timestamp($set = null) {
		if ($set !== null) {
			// 03:14:08 UTC on 19 January 2038 is MAX time using 32-bit integers
			$this->_datetime()->setTimestamp($set);
			return $this;
		}
		return $this->datetime ? $this->datetime->getTimestamp() : null;
	}

	/**
	 * was fromLocaleString
	 *
	 * @param unknown_type $value
	 * @param unknown_type $locale_format
	 * @throws Exception_Convert
	 * @return boolean
	 */
	function parse_locale_string($value, $locale_format = "MDY;MD;MY;_") {
		$value = preg_replace("/[^0-9]/", " ", $value);
		$value = trim(preg_replace('/\s+/', " ", $value));
		$values = explode(" ", $value);
		$this->set_now();
		if (!is_array($locale_format)) {
			$locale_format = explode(";", $locale_format);
		}
		foreach ($locale_format as $dcodes) {
			$dcodes = str_split($dcodes, 1);
			if (count($values) !== count($dcodes)) {
				continue;
			}
			$this->month(1);
			$this->day(1);
			$failed = false;
			foreach ($dcodes as $i => $dcode) {
				switch (strtoupper($dcode)) {
					case "_":
						if (strlen($value) == 8) {
							return $this->parse_locale_string(substr($value, 0, 2) . "/" . substr($value, 2, 2) . "/" . substr($value, 4));
						}
						throw new Exception_Convert("Timestamp::parse_locale_string($value,$locale_format): Unknown format");
					case "M":
						$this->month($values[$i]);
						break;
					case "D":
						$this->day($values[$i]);
						break;
					case "Y":
						$this->year($values[$i]);
						break;
				}
			}
			if (!$failed) {
				return true;
			}
		}
		return false;
	}

	/**
	 * English month names
	 *
	 * @return array
	 */
	private function _month_names_en() {
		static $m = array(
			"jan" => 1,
			"feb" => 2,
			"mar" => 3,
			"apr" => 4,
			"may" => 5,
			"jun" => 6,
			"jul" => 7,
			"aug" => 8,
			"sep" => 9,
			"oct" => 10,
			"nov" => 11,
			"dec" => 12
		);
		return $m;
	}

	/**
	 * Parse a date string
	 *
	 * @param unknown $value
	 * @throws Exception_Convert
	 * @return Timestamp
	 */
	function parse($value) {
		// This fails on a cookie date sent by 64-bit systems
		// Set-Cookie: TrkCookieID=51830899; expires=Sat, 16-Aug-2064 04:11:10 GMT
		// DAY, DD-MMM-YYYY HH:MM:SS GMT
		$matches = null;
		if (preg_match('/([0-9]{2})-([A-Z]{3})-([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])/i', $value, $matches)) {
			$mm = avalue($this->_month_names_en(), strtolower($matches[2]), 1);
			$this->ymd($matches[3], $mm, $matches[1]);
			$this->hms($matches[4], $matches[5], $matches[6]);
			return $this;
		}
		$this->datetime = date_create($value);
		if (!$this->datetime) {
			throw new Exception_Convert(__("Timestamp::parse({0})", $value));
		}
		return $this;
	}

	/**
	 * Get/Set year
	 *
	 * @param string $set
	 * @return Timestamp number
	 */
	function year($set = null) {
		if ($set !== null) {
			$this->_datetime()->setDate($set, $this->month(), $this->day());
			return $this;
		}
		return $this->datetime ? intval($this->datetime->format(self::format_year)) : null;
	}

	/**
	 * Set a 1-based quarter (1,2,3,4)
	 *
	 * @param integer $set
	 * @return Timestamp, number
	 */
	function quarter($set = null) {
		if ($set !== null) {
			if ($set < 1 || $set > 4) {
				throw new Exception_Range(__("Timestamp::quarter({0})", _dump($set)));
			}
			$set = abs($set - 1) % 4;
			$quarter = $this->quarter() - 1;
			if ($quarter === $set) {
				return $this;
			}
			$this->add(0, ($set - $quarter) * 3);
			return $this;
		}
		return $this->datetime ? intval(($this->month() - 1) / 4) + 1 : null;
	}

	/**
	 * Get/Set month
	 *
	 * @param string $set
	 * @return Timestamp number
	 */
	function month($set = null) {
		if ($set !== null) {
			if ($set < 0 || $set > 12) {
				throw new Exception_Range("Month must be between 1 and 12 ({0} passed)", array(
					$set
				));
			}
			$this->_datetime()->setDate($this->year(), $set, $this->day());
			return $this;
		}
		return $this->datetime ? intval($this->datetime->format(self::format_month)) : null;
	}

	/**
	 * Get/Set day of month
	 *
	 * @param string $set
	 * @return Timestamp number
	 */
	function day($set = null) {
		if ($set !== null) {
			if ($set < 0 || $set > 31) {
				throw new Exception_Range("Month must be between 1 and 12 ({0} passed)", array(
					$set
				));
			}
			$this->_datetime()->setDate($this->year(), $this->month(), $set);
			return $this;
		}
		return $this->datetime ? intval($this->datetime->format(self::format_day)) : null;
	}

	function today($set = null) {
		if ($set === null) {
			return $this->datetime->format('Y-m-d') === date('Y-m-d');
		}
		return $this->year(date('Y'))->month(date('n'))->day(date('j'));
	}

	/**
	 * Set to the past weekday specified
	 *
	 * @param integer $set
	 * @return Timestamp
	 */
	function weekday_past($set) {
		return $this->weekday($set)->add_unit("day", -7);
	}
	/**
	 * Get/set weekday. Weekday, when set, is always the NEXT possible weekday, including today.
	 *
	 * @param string $set
	 * @return number Timestamp
	 */
	function weekday($set = null) {
		if ($set === null) {
			return $this->datetime ? intval($this->datetime->format(self::format_weekday)) : null;
		}
		$set = abs($set) % 7;
		$weekday = $this->weekday();
		if ($weekday === $set) {
			return $this;
		}
		if ($weekday < $set) {
			$dd = $set - $weekday;
		} else {
			$dd = 7 + $set - $weekday;
		}
		return $this->add(0, 0, $dd);
	}

	/**
	 * Get/set yearday
	 *
	 * @param string $set
	 * @return number Timestamp
	 */
	function yearday($set = null) {
		if ($set === null) {
			return $this->datetime ? intval($this->datetime->format(self::format_yearday)) : null;
		}
		$yearday = $this->yearday();
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Get/set hour of day
	 *
	 * @param string $set
	 * @return number Timestamp
	 */
	function hour($set = null) {
		if ($set === null) {
			return $this->datetime ? intval($this->datetime->format(self::format_hour)) : null;
		}
		$this->_datetime()->setTime($set, $this->minute(), $this->second());
		return $this;
	}
	/**
	 * Get/set minute of the day
	 *
	 * @param integer $set
	 * @return number Timestamp
	 */
	function minute($set = null) {
		if ($set === null) {
			return $this->datetime ? intval($this->datetime->format(self::format_minute)) : null;
		}
		$this->_datetime()->setTime($this->hour(), $set, $this->second());
		return $this;
	}
	/**
	 * Get/set second of the day
	 *
	 * @param integer $set
	 * @return number Timestamp
	 */
	function second($set = null) {
		if ($set === null) {
			return $this->datetime ? intval($this->datetime->format(self::format_second)) : null;
		}
		$this->_datetime()->setTime($this->hour(), $this->minute(), $set);
		return $this;
	}

	/**
	 *
	 * @return integer
	 */
	function millisecond($set = null) {
		if ($set) {
			$this->_datetime();
			$this->msec = $set % 1000;
			return $this;
		}
		return $this->datetime ? $this->msec : null;
	}

	/**
	 * Number of seconds since midnight
	 *
	 * @return integer
	 */
	function day_seconds($set = null) {
		$midnight = clone $this;
		$midnight->midnight();
		return $this->difference($midnight);
	}

	/**
	 * Get/Set 12-hour
	 *
	 * @param string $set
	 * @return Ambigous <Timestamp, unknown>
	 */
	function hour12($set = null) {
		if ($set === null) {
			if ($this->datetime === null) {
				return null;
			}
			$hour = $this->hour % 12;
			return ($hour === 0) ? 12 : $hour;
		}
		$set = $set % 12;
		// Retains AM/PM
		return $this->hour($set + ($this->hour() < 12 ? 0 : 12));
	}

	/**
	 * Get AMPM
	 */
	function ampm() {
		return $this->Time->ampm();
	}

	/**
	 * Set time to midnight
	 *
	 * @return Timestamp
	 */
	function midnight() {
		$this->_datetime()->setTime(0, 0, 0);
		return $this;
	}
	function noon() {
		$this->_datetime()->setTime(12, 0, 0);
		return $this;
	}

	/**
	 * Set the Year/Month/Date for this Timestamp
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @return Timestamp
	 */
	function ymd($year = null, $month = null, $day = null) {
		$this->_datetime()->setDate($year === null ? $this->year() : $year, $month === null ? $this->month() : $month, $day === null ? $this->day() : $day);
		return $this;
	}

	/**
	 * Set the Hour/Minute/Second for this Timestamp
	 *
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Timestamp
	 */
	function hms($hour = null, $minute = null, $second = null) {
		$this->_datetime()->setTime($hour === null ? $this->hour() : $hour, $minute === null ? $this->minute() : $minute, $second === null ? $this->second() : $second);
		return $this;
	}

	/**
	 * Set the Year/Month/Date/Hour/Minute/Second for this Timestamp
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Timestamp
	 */
	function ymdhms($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null) {
		return $this->ymd($year, $month, $day)->hms($hour, $minute, $second);
	}

	/**
	 * Compare two Timestamps, like strcmp
	 * $this->compare($value) < 0		~= ($this < $value) => -
	 * $this->compare($value) < 0		~= ($this > $value)		=> +
	 * $this->compare($value) == 0		~= ($value == $this)	=> 0
	 *
	 * @param Timestamp $value
	 * @return integer
	 */
	function compare(Timestamp $value) {
		if ($value->is_empty() || $this->is_empty()) {
			return null;
		}
		return $this->unix_timestamp() - $value->unix_timestamp();
	}

	/**
	 * Return the difference in seconds between two Timestamps
	 *
	 * @param Timestamp $value
	 * @return integer
	 */
	function subtract(Timestamp $value) {
		return $this->unix_timestamp() - $value->unix_timestamp();
	}

	/**
	 * Format a Timestamp in the locale, using a formatting string
	 *
	 * @param string $format_string
	 *        	Uses global "Timestamp::format_string" if not specified
	 * @param string $locale
	 *        	Locale to use, if any
	 * @return string
	 */
	function format($format_string = null, $locale = null) {
		if ($format_string === null) {
			$format_string = zesk::get("Timestamp::format_string", self::default_format);
		}
		return map($format_string, $this->formatting($locale));
	}

	/**
	 * Formatting a timestamp string
	 *
	 * @param string $locale
	 *        	Locale to use, if any
	 * @return string
	 */
	function formatting($locale = null) {
		$ts = $this->unix_timestamp();
		$formatting = $this->date()->formatting($locale) + $this->time()->formatting($locale) + array(
			'seconds' => $ts,
			'unix_timestamp' => $ts
		);
		$formatting = zesk::hook_array('Timestamp::formatting', array(
			$this,
			$formatting
		), $formatting);
		return $formatting;
	}

	/**
	 * Are these two timestamps identical?
	 *
	 * @param Timestamp $timestamp
	 * @return boolean
	 */
	function equals(Timestamp $timestamp) {
		return $this->__toString() === $timestamp->__toString();
	}

	/**
	 * Is passed in Timestamp before $this?
	 *
	 * @param Timestamp $model
	 * @param boolean $equal
	 *        	Return true if they are equal
	 * @return boolean
	 */
	function before(Timestamp $model, $equal = false) {
		$result = $this->compare($model);
		if ($equal) {
			return ($result <= 0) ? true : false;
		} else {
			return ($result < 0) ? true : false;
		}
	}

	/**
	 * Is passed in Timestamp after $this?
	 *
	 * @param Timestamp $model
	 * @param boolean $equal
	 *        	Return true if they are equal
	 * @return boolean
	 */
	function after(Timestamp $model, $equal = false) {
		$result = $this->compare($model);
		if ($equal) {
			return ($result >= 0) ? true : false;
		} else {
			return ($result > 0) ? true : false;
		}
	}

	/**
	 * Given another model and this, return the one which is later.
	 * If both are empty, returns $this
	 * If identical, returns $this
	 *
	 * @param Timestamp $model
	 * @return Timestamp
	 */
	function later(Timestamp $model) {
		if ($model->is_empty()) {
			return $this;
		}
		if ($this->is_empty()) {
			return $model;
		}
		return $model->after($this) ? $model : $this;
	}
	function is_past() {
		if ($this->is_empty()) {
			return false;
		}
		return ($this->unix_timestamp() < time());
	}
	/**
	 * Given another model and this, return the one which is earlier.
	 * If both are empty, returns $this
	 * If identical, returns $this
	 *
	 * @param Timestamp $model
	 * @return Timestamp
	 */
	function earlier(Timestamp $model) {
		if ($model->is_empty()) {
			return $this;
		}
		if ($this->is_empty()) {
			return $model;
		}
		return $model->before($this) ? $model : $this;
	}

	/**
	 * Add units to dates
	 *
	 * @param integer $years
	 * @param integer $months
	 * @param integer $days
	 * @param integer $hours
	 * @param integer $minutes
	 * @param integer $seconds
	 * @return Timestamp
	 */
	function add($years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0) {
		if ($this->datetime === null) {
			throw new Exception_Semantics("Adding to a empty Timestamp");
		}
		$this->_add_unit($years, "Y");
		$this->_add_unit($months, "M");
		$this->_add_unit($days, "D");
		$this->_add_unit($hours, "H", true);
		$this->_add_unit($minutes, "M", true);
		$this->_add_unit($seconds, "S", true);
		return $this;
	}

	/**
	 * Utility for add
	 *
	 * @param number $number
	 * @param string $code
	 * @param boolean $time
	 * @return Timestamp
	 */
	function _add_unit($number, $code, $time = false) {
		$interval = new DateInterval("P" . ($time ? "T" : "") . abs($number) . $code);
		if ($number < 0) {
			$interval->invert = true;
		}
		$this->datetime->add($interval);
		return $this;
	}

	/*
	 *
	 */
	/**
	 * Determine the difference between two dates based on a unit.
	 * $early $later
	 * |-----------------|--------> future
	 * $early->difference($later) => NEGATIVE
	 * $later->difference($early) => POSITIVE
	 * e.g.
	 * $a->difference($b) equivalent to ($a - $b)
	 *
	 * @param Timestamp $timestamp
	 * @param string $unit
	 *        	millisecond, second, minute, hour, day, week, weekday, quarter
	 * @param integer $precision
	 *        	The precision for the result (decimal places to use)
	 * @throws Exception_Parameter
	 * @return number
	 */
	function difference(Timestamp $timestamp, $unit = "second", $precision = 0) {
		if ($timestamp->after($this, false)) {
			return -$timestamp->difference($this, $unit, $precision);
		}
		if ($unit === "weekday") {
			return $this->weekday() - $timestamp->weekday();
		}
		$precision = intval($precision);
		$delta = $this->subtract($timestamp);
		switch ($unit) {
			case "millisecond":
				return $delta * 1000;
			case "second":
				return $delta;
			case "minute":
				return round($delta / 60.0, $precision);
			case "hour":
				return round($delta / 3600.0, $precision);
			case "day":
				return round($delta / 86400, $precision);
			case "week":
				return round($delta / (86400 * 7), $precision);
		}

		$mstart = $timestamp->month();
		$ystart = $timestamp->year();

		$mend = $this->month();
		$yend = $this->year();

		if ($precision === 0) {
			switch ($unit) {
				case "month":
					return ($yend - $ystart) * 12 + ($mend - $mstart);
				case "quarter":
					$mend = intval($mend / 4);
					$mstart = intval($mstart / 4);
					return ($yend - $ystart) * 4 + ($mend - $mstart);
				case "year":
					return ($yend - $ystart);
				default:
					throw new Exception_Parameter("Date::difference($timestamp, $unit): Bad unit");
			}
		} else {
			// Works like so:
			//
			// 2/22 -> 3/22 = 1 month
			// 2/12 -> 3/22 = 1 month + ((3/22-2/22) / 28)


			$intmon = ($yend - $ystart) * 12 + ($mend - $mstart);
			$total = Date::days_in_month($mstart, $ystart);

			$temp = clone $timestamp;
			$temp->setMonth($mstart);
			$temp->setYear($ystart);

			$fract = $temp->subtract($this);
			$fract = $fract / doubleval($total * 86400);

			switch ($unit) {
				case "month":
					$result = round($intmon + $fract, $precision);
					break;
				case "quarter":
					$result = round(($intmon + $fract) / 3, $precision);
					break;
				case "year":
					$result = round(($intmon + $fract) / 12, $precision);
					break;
				default:
					throw new Exception_Parameter("Date::difference($timestamp, $unit): Bad unit");
			}
			return $result;
		}
	}

	/**
	 * Set or get a unit.
	 *
	 * @param string $unit
	 *        	One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @param integer $value
	 *        	Value to set, or null to get
	 * @throws Exception_Parameter
	 * @return Timestamp integer
	 */
	function unit($unit, $value = null) {
		switch ($unit) {
			case "millisecond":
				if ($value !== null) {
					return $this->second(round($value / 1000));
				} else {
					return $this->milliseconds();
				}
			case "second":
			case "minute":
			case "hour":
			case "weekday":
			case "day":
			case "month":
			case "quarter":
			case "year":
				return $this->$unit($value);
			default:
				throw new Exception_Parameter("Timestamp::unit($unit, $value): Bad unit");
		}
	}

	/**
	 * Add a unit to this Timestamp
	 *
	 * @param string $unit
	 *        	One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @param integer $n
	 *        	Number of units to add (may be negative)
	 * @throws Exception_Parameter
	 * @return Timestamp
	 */
	function add_unit($unit = "second", $n = 1) {
		switch ($unit) {
			case "millisecond":
				return $this->add(0, 0, 0, 0, 0, round($n / 1000));
			case "second":
				return $this->add(0, 0, 0, 0, 0, $n);
			case "minute":
				return $this->add(0, 0, 0, 0, $n);
			case "hour":
				return $this->add(0, 0, 0, $n);
			case "weekday":
			case "day":
				return $this->add(0, 0, $n);
			case "week":
				return $this->add(0, 0, $n * 7);
			case "month":
				return $this->add(0, $n);
			case "quarter":
				return $this->add(0, $n * 3);
			case "year":
				return $this->add($n);
			default:
				throw new Exception_Parameter("Date::addUnit($unit, $n): Bad unit");
		}
	}

	/**
	 * Get or set a iso8601 date format
	 *
	 * @param string $set
	 *        	Date to set as an integer timestamp, or an ISO8601 formatted date
	 * @see http://en.wikipedia.org/wiki/ISO_8601
	 * @throws Exception_Syntax
	 * @return Timestamp string
	 */
	function iso8601($set = null) {
		if ($set !== null) {
			if (is_numeric($set)) {
				return $this->unix_timestamp($set);
			}
			$value = trim($set);
			// if (preg_match('/[0-9]{4}-([0-9]{2}-[0-9]{2}|W[0-9]{2}|[0-9]{3})(T[0-9]{2}(:?[0-9]{2}(:?[0-9]{2}))/',
			// $value, $matches)) {
			// TODO support iso8601 latest
			// }
			list($dd, $tt) = pair(strtoupper($value), "T");
			if ($dd === "0000") {
				$this->set_empty();
				return $this;
			}
			if ($dd === false || $tt === false) {
				throw new Exception_Syntax(__("Timestamp::iso8601({0}) - invalid date format", $set));
			}
			list($hh, $mm, $ss) = explode(":", $tt, 3);
			$this->ymdhms(substr($dd, 0, 4), substr($dd, 4, 2), substr($dd, 6, 2), $hh, $mm, $ss);
			return $this;
		}
		$result = "";
		$result .= $this->year() . str::zero_pad($this->month()) . str::zero_pad($this->day());
		$result .= "T" . str::zero_pad($this->hour()) . ":" . str::zero_pad($this->minute()) . ":" . str::zero_pad($this->second());
		return $result;
	}

	/**
	 * Return an array of unit => seconds (integer)
	 *
	 * @return array
	 */
	public static function units_translation_table($unit = null) {
		$result = array(
			"year" => 31536000,  // 365*86400 days
			"month" => 2628000,  // 365*86400/12 (average 30.42 days)
			"week" => 604800,  // 60*60*24*7
			"day" => 86400,  // 60*60*24
			"hour" => 3600,  // 60*60
			"minute" => 60,  // 60
			"second" => 1
		); // 1
		if ($unit !== null) {
			return avalue($result, $unit, $result);
		}
		return $result;
	}

	/**
	 * Convert seconds into a particular unit
	 *
	 * @param integer $seconds
	 *        	Number of seconds to convert to a unit.
	 * @param string $stop_unit
	 *        	Unit to stop comparing for. If you only want to know how many months away
	 *        	something is,
	 *        	specify a higher value.
	 * @param double $fraction
	 *        	Returns $seconds divided by total units, can be used to specify 2.435 years, for
	 *        	example.
	 * @return string The units closest to the number of seconds
	 */
	public static function seconds_to_unit($seconds, $stop_unit = "second", &$fraction = null) {
		$seconds = intval($seconds);
		$translation = self::units_translation_table();
		foreach ($translation as $unit => $unit_seconds) {
			if (($seconds > $unit_seconds) || ($stop_unit === $unit)) {
				$fraction = intval($seconds / $unit_seconds);
				return $unit;
			}
		}
		$fraction = $seconds;
		return $unit;
	}

	/**
	 * Create a new Timestamp with explicit values
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Timestamp
	 * @deprecated 2014-10 Improperly named singleton class
	 * @see Timestamp::factory_ymdhms
	 */
	public static function instance($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null) {
		$dt = new Timestamp(null);
		$dt->ymd($year, $month, $day);
		$dt->hms($hour, $minute, $second);
		return $dt;
	}

	/**
	 * Create a new Timestamp with explicit values
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Timestamp
	 */
	public static function factory_ymdhms($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null) {
		$dt = new Timestamp(null);
		$dt->ymd($year, $month, $day);
		$dt->hms($hour, $minute, $second);
		return $dt;

	}
}

