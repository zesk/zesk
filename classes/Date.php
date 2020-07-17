<?php

/**
 * Date
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 * @subpackage model
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 * @see Base,DateTime,Time
 */
namespace zesk;

/**
 * Date representing a Month, Day, and Year
 *
 * @author kent
 */
class Date extends Temporal {
	/**
	 *
	 * @var string
	 */
	const DEFAULT_FORMAT_STRING = "{YYYY}-{MM}-{DD}";

	/**
	 * Set up upon load
	 *
	 * @var string
	 */
	private static $default_format_string = self::DEFAULT_FORMAT_STRING;

	/**
	 *
	 * @var integer
	 */
	const seconds_in_day = 86400;

	/**
	 * Year 2000+
	 *
	 * @var integer
	 */
	protected $year;

	/**
	 * Month 1-12
	 *
	 * @var integer
	 */
	protected $month;

	/**
	 * Day 1-31
	 *
	 * @var integer
	 */
	protected $day;

	/**
	 * Day of the week 0-6
	 *
	 * @var integer
	 */
	private $_weekday = null;

	/**
	 * Yearday 0-365
	 *
	 * @var integer
	 */
	private $_yearday = null;

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
	 * Construct a new date instance with specific Year/Month/Day
	 * Pass a negative value to use current year/month/date
	 *
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @return Date
	 */
	public static function instance($year = null, $month = null, $day = null) {
		$d = new Date();
		$d->ymd($year, $month, $day);
		return $d;
	}

	/**
	 * Create a Date object
	 *
	 * @param mixed $value
	 * @return Date
	 */
	public static function factory($value = null) {
		return new Date($value);
	}

	/**
	 * Construct a new Date object
	 *
	 * @param mixed $value
	 * @see Date::set
	 */
	public function __construct($value = null) {
		$this->_weekday = null;
		$this->_yearday = null;

		$this->set($value);
	}

	/**
	 *
	 * @return self
	 */
	public function duplicate() {
		return clone $this;
	}

	/**
	 * Return a new Date object with the current date
	 *
	 * @return Date
	 */
	public static function now() {
		$d = new Date();
		return $d->set_now();
	}

	/**
	 * Return a new Date object with the current date
	 *
	 * @return Date
	 */
	public static function utc_now() {
		$d = new Date();
		return $d->set_utc_now();
	}

	/**
	 * Is this date empty? e.g.
	 * not set to any value
	 *
	 * @return boolean
	 */
	public function is_empty() {
		return ($this->year === null);
	}

	/**
	 * Make this Date value empty
	 *
	 * @return Date
	 */
	public function set_empty() {
		$this->year = null;
		return $this;
	}

	/**
	 * Set the date with a variety of values
	 *
	 * @param mixed $value
	 * @throws Exception_Convert
	 * @return boolean
	 */
	public function set($value) {
		if (empty($value)) {
			$this->set_empty();
			return $this;
		}
		if (is_integer($value)) {
			$this->unix_timestamp($value);
			return $this;
		}
		if (is_string($value)) {
			return $this->parse($value);
		}
		if (!is_object($value)) {
			throw new Exception_Convert("{method}({value})", array(
				"method" => __METHOD__,
				"value" => $value,
			));
		}
		if ($value instanceof Date || $value instanceof Timestamp) {
			return $this->unix_timestamp($value->unix_timestamp());
		}
		return $this->set(strval($value));
	}

	/**
	 * Convert a Date to a standard string representation
	 *
	 * @return string
	 */
	public function __toString() {
		if ($this->is_empty()) {
			return "";
		}
		return $this->year() . "-" . StringTools::zero_pad($this->month()) . "-" . StringTools::zero_pad($this->day());
	}

	/**
	 * Parse a Date from a variety of formats
	 *
	 * @see strtotime
	 * @return Date
	 */
	public function parse($value) {
		$ts = @strtotime($value, $this->unix_timestamp());
		if ($ts < 0 || $ts === false) {
			throw new Exception_Parse(map("Date::fromString({value})", array("value" => _dump($value))));
		}
		return $this->unix_timestamp($ts);
	}

	public function set_now() {
		return $this->_set_date(getdate());
	}

	public function set_utc_now() {
		return $this->set(time());
	}

	public function unix_timestamp($set = null) {
		if ($set === null) {
			$ts = gmmktime(0, 0, 0, $this->month, $this->day, $this->year);
			if ($ts === false) {
				throw new Exception_Convert("Date::unix_timestamp({0}): Year is out of range of integer", $set);
			}
			return $ts;
		}
		if (!is_numeric($set)) {
			throw new Exception_Parameter("Date::unix_timestamp({0}): Invalid unix timestamp (numeric)", $set);
		}
		return $this->_set_date(getdate(intval($set)));
	}

	public function ymd($yy = null, $mm = null, $dd = null) {
		$this->year($yy);
		$this->month($mm);
		$this->day($dd);
		return $this;
	}

	private function _set_date($date) {
		if (!is_array($date)) {
			throw new Exception_Parameter("Date::_set_date() expects array from getdate()");
		}
		return $this->ymd($date["year"], $date["mon"], $date["mday"]);
	}

	public function month($set = null) {
		if ($set === null) {
			return intval($this->month);
		}
		if ($set < 1 || $set > 12) {
			throw new Exception_Range(map("Date::setMonth({0})", array(_dump($set))));
		}
		if ($this->month !== $set) {
			$this->_yearday = $this->_weekday = null;
		}
		$this->month = $set;
		return $this;
	}

	/**
	 * Set or get 1-based quarter number
	 *
	 * @param integer $set
	 *        	Set 1-based quarter (1,2,3,4)
	 * @return number Date
	 */
	public function quarter($set = null) {
		if ($set === null) {
			return intval(($this->month - 1) / 3) + 1;
		}
		if ($set < 1 || $set > 4) {
			throw new Exception_Range(map("Date::quarter({0})", array(_dump($set))));
		}
		// Convert to zero-based quarter
		$set = abs($set - 1) % 4;
		$quarter = $this->quarter() - 1;
		if ($quarter === $set) {
			return $this;
		}

		// ($set - $quarter)
		// ========+====+====+====+====+
		// | 0 | 1 | 2 | 3 | $set
		// ========+====+====+====+====+
		// 0 | X | 1 | 2 | 3 |
		// 1 | -1 | X | 1 | 2 |
		// 2 | -2 | -1 | X | 1 |
		// 3 | -3 | -2 | -1 | X |
		// ========+====+====+====+====+
		// $quarter

		/*
		 * if ($quarter < $set) { // positive #s $this->add(0, ($set - $quarter) * 3); } if ($quarter > $set) { //
		 * negative #s $this->add(0, (4 - ($quarter - $set)) * 3); }
		 */
		$this->add(0, ($set - $quarter) * 3);
		return $this;
	}

	public function day($set = null) {
		if ($set === null) {
			return intval($this->day);
		}
		$set = intval($set);
		if ($set < 1 || $set > 31) {
			throw new Exception_Range(map("Date::day({0})", array(_dump($set))));
		}
		if ($this->day !== $set) {
			$this->_weekday = $this->_yearday = null;
		}
		$this->day = intval($set);
		;
		return $this;
	}

	/**
	 *
	 * @param integer $set
	 * @throws Exception_Range
	 * @return number|\zesk\Date
	 */
	public function year($set = null) {
		if ($set === null) {
			return $this->year;
		}
		if ($set < 0) {
			throw new Exception_Range(map("Date::year({0})", array(_dump($set))));
		}
		if ($this->year !== $set) {
			$this->_weekday = $this->_yearday = null;
		}
		$this->year = intval($set);
		return $this;
	}

	public function weekday($set = null) {
		if ($set === null) {
			if (($this->_weekday === null) && (!$this->_refresh())) {
				return null;
			}
			return $this->_weekday;
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

	public function yearday($set = null) {
		if ($set === null) {
			if (($this->_yearday === null) && (!$this->_refresh())) {
				return false;
			}
			return $this->_yearday;
		}
		$yearday = $this->yearday();
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Returns the last day of the month (or number of days in the month)
	 *
	 * @see self::days_in_month
	 * @return integer
	 */
	public function lastday() {
		return self::days_in_month($this->month, $this->year);
	}

	/**
	 *
	 * @param integer $month
	 * @param integer $year
	 * @return NULL|integer
	 */
	public static function days_in_month($month, $year) {
		$month = intval($month);
		$year = intval($year);
		$daysInMonth = array(
			1 => 31,
			2 => 28,
			3 => 31,
			4 => 30,
			5 => 31,
			6 => 30,
			7 => 31,
			8 => 31,
			9 => 30,
			10 => 31,
			11 => 30,
			12 => 31,
		);
		if ($month < 1 || $month > 12) {
			return null;
		}
		if ($month !== 2) {
			return $daysInMonth[$month];
		}
		if (checkdate($month, 29, $year)) {
			return 29;
		}
		return 28;
	}

	/**
	 * Compare two dates
	 * $this->compare($value) < 0 analagous to $this < $value
	 * $this->compare($value) > 0 analagous to $this > $value
	 *
	 * @param Date $value
	 * @return integer
	 */
	public function compare(Date $value) {
		$result = $this->year - $value->year();
		if ($result === 0) {
			$result = $this->month - $value->month();
			if ($result === 0) {
				$result = $this->day - $value->day();
			}
		}
		return $result;
	}

	/**
	 * Returns true if $date is before $this
	 *
	 * @param Date $date
	 * @param boolean $equal
	 *        	Returns true if $date === $this
	 * @return boolean
	 */
	public function before(Date $date, $equal = false) {
		$result = $this->compare($date);
		if ($equal) {
			return ($result <= 0) ? true : false;
		} else {
			return ($result < 0) ? true : false;
		}
	}

	/**
	 * Returns true if $date is after $this
	 *
	 * @param Date $date
	 * @param boolean $equal
	 *        	Returns true if $date === $this
	 * @return boolean
	 */
	public function after(Date $date, $equal = false) {
		$result = $this->compare($date);
		if ($equal) {
			return ($result >= 0) ? true : false;
		} else {
			return ($result > 0) ? true : false;
		}
	}

	/**
	 * Returns difference in seconds between two dates
	 *
	 * @param Date $date
	 * @return integer
	 */
	public function subtract(Date $value) {
		return $this->unix_timestamp() - $value->unix_timestamp();
	}

	/**
	 * Returns difference in seconds between two dates
	 *
	 * @param Date $date
	 * @param boolean $equal
	 *        	Returns true if $date === $this
	 * @return boolean
	 */
	public function subtract_days(Date $value) {
		return round($this->subtract($value) / self::seconds_in_day);
	}

	/**
	 * Clamp a date to a range.
	 * Does not check that $min_date and $max_date are in the correct order.
	 *
	 * @param Date $min_date
	 *        	Earliest date to clamp to (optional)
	 * @param Date $max_date
	 *        	Latest date to clamp to (optional)
	 * @return boolean true if date was changed, false if nothing changed
	 */
	public function clamp(Date $min_date = null, Date $max_date = null) {
		if ($min_date && $this->before($min_date)) {
			$this->set($min_date);
			return true;
		}
		if ($max_date && $this->after($max_date)) {
			$this->set($max_date);
			return true;
		}
		return false;
	}

	/**
	 * Add years, months, or days to a Date
	 *
	 * @param integer $years
	 * @param integer $months
	 * @param integer $days
	 * @return Date
	 */
	public function add($years = 0, $months = 0, $days = 0) {
		$foo = mktime(0, 0, 0, $this->month + $months, $this->day + $days, $this->year + $years);
		return $this->_set_date(getdate($foo));
	}

	/**
	 * Add units to a date or time
	 *
	 * @param string $units Use self::UNIT_FOO for units
	 * @param integer $n_units
	 * @throws Exception_Parameter
	 * @return Date
	 */
	public function add_unit($n_units = 1, $units = self::UNIT_DAY) {
		/**
		 * Support legacy call syntax
		 *
		 * function add_unit($unit = self::UNIT_DAY, $n = 1)
		 * @deprecated 2017-06
		 */
		if (is_string($n_units) && array_key_exists($n_units, self::$UNITS_TRANSLATION_TABLE)) {
			// Handle 2nd parameter defaults correctly
			if ($units === self::UNIT_SECOND) {
				$units = 1;
			}
			/* Swap */
			list($n_units, $units) = array(
				$units,
				$n_units,
			);
			zesk()->deprecated("{method} called with {n_units} {units} first", array(
				"method" => __METHOD__,
				"n_units" => $n_units,
				"units" => $units,
			));
		}
		switch ($units) {
			case self::UNIT_WEEKDAY:
			case self::UNIT_DAY:
				return $this->add(0, 0, $n_units);
			case self::UNIT_WEEK:
				return $this->add(0, 0, $n_units * self::DAYS_PER_WEEK);
			case self::UNIT_MONTH:
				return $this->add(0, $n_units);
			case self::UNIT_QUARTER:
				return $this->add(0, $n_units * self::MONTHS_PER_QUARTER);
			case self::UNIT_YEAR:
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
	 * Month names in English
	 *
	 * @var array
	 */
	private static $months = array(
		1 => "January",
		"February",
		"March",
		"April",
		"May",
		"June",
		"July",
		"August",
		"September",
		"October",
		"November",
		"December",
	);

	/**
	 * Month names translated
	 *
	 * @var array
	 */
	private static $translated_months = array();

	/**
	 * Weekday names in English
	 *
	 * @var array
	 */
	private static $weekday_names = array(
		0 => "Sunday",
		1 => "Monday",
		2 => "Tuesday",
		3 => "Wednesday",
		4 => "Thursday",
		5 => "Friday",
		6 => "Saturday",
	);

	/**
	 * Weekday names translated
	 *
	 * @var array
	 */
	private static $translated_weekdays = array();

	/**
	 * Return a localized list of month dates, 1-index based
	 *
	 * @param string $locale
	 *        	Locale to translate to
	 * @param string $short
	 *        	Short dates
	 * @return string
	 */
	public static function month_names(Locale $locale, $short = false) {
		$id = $locale->id();
		$short = intval(boolval($short));
		if (isset(self::$translated_months[$id][$short])) {
			return self::$translated_months[$id][$short];
		}
		$locale_months = array();
		foreach (self::$months as $i => $month) {
			$locale_months[$i] = $locale($short ? "Date-short:=" . substr($month, 0, 3) : "Date:=" . $month);
		}
		self::$translated_months[$id][$short] = $locale_months;
		return $locale_months;
	}

	/**
	 *
	 * @param Locale $locale
	 * @param string $short
	 * @return string[]
	 */
	public static function weekday_names(Locale $locale, $short = false) {
		$id = $locale->id();
		$short = intval(boolval($short));
		if (isset(self::$translated_weekdays[$id][$short])) {
			return self::$translated_weekdays[$id][$short];
		}
		$locale_weekdays = array();
		foreach (self::$weekday_names as $k => $v) {
			$locale_weekdays[$k] = $locale($short ? 'Date-short:=' . substr($v, 0, 3) : 'Date:=' . $v);
		}
		self::$translated_weekdays[$id][$short] = $locale_weekdays;
		return $locale_weekdays;
	}

	/**
	 * Format YYYY${sep}MM${sep}DD
	 *
	 * @param string $sep
	 * @return string
	 */
	private function _ymd_format($sep = "-") {
		return $this->year() . $sep . StringTools::zero_pad($this->month()) . $sep . StringTools::zero_pad($this->day());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Temporal::sql()
	 */
	public function sql() {
		return $this->_ymd_format();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Temporal::formatting()
	 */
	public function formatting(Locale $locale = null, array $options = array()) {
		// $old_locale = setlocale(LC_TIME,0);
		// setlocale(LC_TIME, $locale);
		// $ts = $this->toTimestamp();
		$m = $this->month();
		$w = $this->weekday();
		$d = $this->day();

		$x = array();
		$x['M'] = $m;
		$x['D'] = $this->day();
		$x['W'] = $w;

		foreach ($x as $k => $v) {
			$x[$k . $k] = StringTools::zero_pad($v, 2);
		}

		$x['YYYY'] = $this->year();
		$x['YY'] = substr($this->year(), -2);

		if ($locale) {
			$x['MMMM'] = avalue($this->month_names($locale), $m, "?");
			$x['MMM'] = avalue($this->month_names($locale, true), $m, "?");

			$x['DDD'] = $locale->ordinal($d);

			if ($w !== null) {
				$x['WWWW'] = avalue($this->weekday_names($locale), $w, "?");
				$x['WWW'] = avalue($this->weekday_names($locale, true), $w, "?");
			}
		}
		return $x;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Temporal::format()
	 */
	public function format(Locale $locale = null, $format_string = null, array $options = array()) {
		if ($format_string === null) {
			$format_string = self::$default_format_string;
		}
		$formatting = $this->formatting($locale, $options);
		return map($format_string, $formatting);
	}

	/**
	 * Set/get date as an integer (UNIX timestamp)
	 *
	 * @param unknown $set
	 * @return number|\zesk\Date
	 */
	public function integer($set = null) {
		return $this->unix_timestamp($set);
	}

	public function equals(Date $d) {
		return $d->__toString() === $this->__toString();
	}

	public function equal_years(Date $d) {
		return $d->year() === $this->year();
	}

	public function equal_months(Date $d) {
		return $d->month() === $this->month();
	}

	public function is_last_day_of_month() {
		return ($this->day === $this->days_in_month($this->month, $this->year));
	}

	private function _refresh() {
		if ($this->is_empty()) {
			return false;
		}
		$date = getdate(mktime(0, 0, 0, $this->month, $this->day, $this->year));
		$this->_weekday = $date["wday"];
		$this->_yearday = $date["yday"];
		return true;
	}

	/**
	 *
	 * @param integer $year
	 * @return boolean
	 */
	public function is_leap_year($year = null) {
		if ($year === null) {
			$year = $this->year;
		}
		return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
	}

	/**
	 *
	 * @var integer
	 */
	private static $gregorian_offset = null;

	/**
	 * Get or set the gregorgian offset from year 1
	 *
	 * @param integer $set
	 * @return \zesk\Date|number
	 */
	public function gregorian($set = null) {
		if (self::$gregorian_offset === null) {
			self::$gregorian_offset = gregoriantojd(1, 1, 1) - 1;
		}
		if ($set !== null) {
			list($month, $day, $year) = explode("/", jdtogregorian($set + self::$gregorian_offset), 3);
			return $this->ymd(intval($year), intval($month), intval($day));
		}
		return gregoriantojd($this->month, $this->day, $this->year) - self::$gregorian_offset;
	}
}
