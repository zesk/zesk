<?php
declare(strict_types=1);

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
	public const DEFAULT_FORMAT_STRING = '{YYYY}-{MM}-{DD}';

	/**
	 * Set up upon load
	 *
	 * @var string
	 */
	private static string $default_format_string = self::DEFAULT_FORMAT_STRING;

	/**
	 *
	 * @var integer
	 */
	public const seconds_in_day = 86400;

	/**
	 * Year 2000+
	 *
	 * If null, everything else is invalid (empty)
	 *
	 * @var ?int
	 */
	protected ?int $year = null;

	/**
	 * Month 1-12
	 *
	 * @var integer
	 */
	protected int $month = 1;

	/**
	 * Day 1-31
	 *
	 * @var integer
	 */
	protected int $day = 0;

	/**
	 * Day of the week 0-6
	 *
	 * @var ?int
	 */
	private ?int $_weekday;

	/**
	 * Day of year 0-366
	 *
	 * @var ?int
	 */
	private ?int $_year_day;

	/**
	 * Offset from day 1,1,1 in gregorian calendar, computed first time
	 *
	 * @var ?int
	 */
	private static ?int $gregorian_offset = null;

	/**
	 * @param Application $kernel
	 * @throws Exception_Semantics
	 */
	public static function hooks(Application $kernel): void {
		$kernel->hooks->add(Hooks::HOOK_CONFIGURED, [
			__CLASS__,
			'configured',
		]);
	}

	/**
	 * @param Application $application
	 * @throws Exception_Lock
	 */
	public static function configured(Application $application): void {
		self::$default_format_string = $application->configuration->path_get([
			__CLASS__,
			'format_string',
		], self::DEFAULT_FORMAT_STRING);
	}

	/**
	 * Construct a new date instance with specific Year/Month/Day
	 * Pass a negative value to use current year/month/date
	 *
	 * @param int|null $year
	 * @param int|null $month
	 * @param int|null $day
	 * @return Date
	 * @throws Exception_Range
	 */
	public static function instance(int $year = null, int $month = null, int $day = null): Date {
		$d = new Date();
		$d->ymd($year, $month, $day);
		return $d;
	}

	/**
	 * @param mixed $value
	 * @return Date
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Range
	 */
	public static function factory(mixed $value = null): self {
		return new Date($value);
	}

	/**
	 * Date constructor.
	 * @param null $value
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Range
	 */
	public function __construct(mixed $value = null) {
		$this->_weekday = null;
		$this->_year_day = null;
		if ($value !== null) {
			$this->set($value);
		} else {
			$this->setEmpty();
		}
	}

	/**
	 * Copy that puppy
	 * @return self
	 */
	public function duplicate(): self {
		return clone $this;
	}

	/**
	 * Return a new Date object with the current date
	 *
	 * @return Date
	 */
	public static function now(): self {
		$d = new Date();
		return $d->set_now();
	}

	/**
	 * Return a new Date object with the current date
	 *
	 * @return Date
	 */
	public static function utc_now(): self {
		$d = new Date();
		return $d->set_utc_now();
	}

	/**
	 * Is this date empty? e.g.
	 * not set to any value
	 *
	 * @return boolean
	 */
	public function isEmpty(): bool {
		return ($this->year === null);
	}

	/**
	 * Make this Date value empty
	 *
	 * @return Date
	 */
	public function setEmpty(): self {
		$this->year = null;
		return $this;
	}

	/**
	 * Set the date with a variety of values
	 * @param $value
	 * @return $this|int
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Range
	 */
	public function set(mixed $value): self {
		if (empty($value)) {
			return $this->setEmpty();
		}
		if (is_int($value)) {
			return $this->setUNIXTimestamp($value);
		}
		if (is_string($value)) {
			$this->parse($value);
		}
		if (!is_object($value)) {
			throw new Exception_Convert('{method}({value})', [
				'method' => __METHOD__,
				'value' => $value,
			]);
		}
		if ($value instanceof Date || $value instanceof Timestamp) {
			return $this->setUNIXTimestamp($value->unixTimestamp());
		}
		return $this->set(strval($value));
	}

	/**
	 * @param Date $date
	 * @return $this
	 */
	public function setDate(Date $date): self {
		if ($date->isEmpty()) {
			return $this->setEmpty();
		}
		return $this->setUNIXTimestamp($date->unixTimestamp());
	}

	/**
	 * @return string
	 */
	public function __toString() {
		if ($this->is_empty()) {
			return '';
		}
		return $this->year . '-' . StringTools::zero_pad($this->month) . '-' . StringTools::zero_pad($this->day);
	}

	/**
	 * Parse a Date from a variety of formats
	 *
	 * @return Date
	 * @see strtotime
	 */
	/**
	 * @param $value
	 * @return $this|int
	 * @throws Exception_Convert
	 */

	/**
	 * @param $value
	 * @return int
	 * @throws Exception_Convert
	 * @throws Exception_Parse
	 */
	public function parse($value): self {
		$ts = @strtotime($value, $this->unixTimestamp());
		if ($ts < 0 || $ts === false) {
			throw new Exception_Parse(map('Date::fromString({value})', ['value' => _dump($value)]));
		}
		return $this->setUNIXTimestamp($ts);
	}

	/**
	 * @return $this
	 */
	public function set_now() {
		try {
			return $this->_setDateArray(getdate());
		} catch (Exception_Range $e) {
			return null;
		}
	}

	/**
	 * @return $this
	 */
	public function set_utc_now() {
		try {
			return $this->unix_timestamp(time());
		} catch (\Exception $e) {
			return $this;
		}
	}

	/**
	 * @return int
	 * @throws Exception_Convert
	 */
	public function unixTimestamp() {
		$ts = gmmktime(0, 0, 0, $this->month, $this->day, $this->year);
		if ($ts === false) {
			throw new Exception_Convert("Date::unixTimestamp gmmktime returned false for (0, 0, 0, $this->month, $this->day, $this->year)");
		}
		return $ts;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Range
	 */
	public function setUNIXTimestamp(int $set): self {
		return $this->_setDateArray(getdate($set));
	}

	/**
	 * @param integer $yy
	 * @param integer $mm
	 * @param integer $dd
	 * @return $this
	 * @throws Exception_Range
	 */
	public function ymd(int $yy = null, int $mm = null, int $dd = null) {
		if ($yy !== null) {
			$this->setYear($yy);
		}
		if ($mm !== null) {
			$this->setMonth($mm);
		}
		if ($dd !== null) {
			$this->setDay($dd);
		}
		return $this;
	}

	/**
	 * @param array $date getdate result
	 * @return $this
	 * @throws Exception_Range
	 */
	private function _setDateArray(array $date): self {
		$this->ymd($date['year'], $date['mon'], $date['mday']);
		return $this;
	}

	/**
	 * @return integer
	 */
	protected function _month(): int {
		return $this->month;
	}

	/**
	 * @param int|null $set Set value
	 * @return int
	 * @throws Exception_Range
	 */
	public function month(int $set = null): int {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setMonth($set);
		}
		return $this->month;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws Exception_Range
	 */
	public function setMonth(int $set): self {
		if ($set < 1 || $set > 12) {
			throw new Exception_Range(map('Date::setMonth({0})', [_dump($set)]));
		}
		if ($this->month !== $set) {
			$this->_year_day = $this->_weekday = null;
		}
		$this->month = $set;
		return $this;
	}

	/**
	 * Set or get 1-based quarter number
	 *
	 * @param integer $set Set 1-based quarter (1,2,3,4)
	 * @return integer|self
	 * @throws Exception_Range
	 */
	public function quarter($set = null): int {
		if ($set === null) {
			zesk()->deprecated('setter');
			$this->setQuarter(intval($set));
		}
		return intval(($this->month - 1) / 3) + 1;
	}

	/**
	 * Set or get 1-based quarter number
	 *
	 * @param integer $set Set 1-based quarter (1,2,3,4)
	 * @return integer|self
	 * @throws Exception_Range
	 */
	public function setQuarter(int $set): self {
		if ($set < 1 || $set > 4) {
			throw new Exception_Range(map('Date::quarter({0})', [_dump($set)]));
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

	/**
	 * @param integer|null $set
	 * @return $this|integer
	 * @throws Exception_Range
	 */
	public function day(int $set = null): int {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setDay($set);
		}
		return $this->day;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws Exception_Range
	 */
	public function setDay(int $set): self {
		if ($set < 1 || $set > 31) {
			throw new Exception_Range(map('Date::day({0})', [_dump($set)]));
		}
		if ($this->day !== $set) {
			$this->_weekday = $this->_year_day = null;
		}
		$this->day = intval($set);

		return $this;
	}

	/**
	 * @return integer
	 */
	protected function _year() {
		return $this->year;
	}

	/**
	 *
	 * @param int $set
	 * @return int
	 */
	public function year(int $set = null): int {
		if ($set != null) {
			zesk()->deprecated('setter');
			$this->setYear(intval($set));
		}
		return $this->year;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws Exception_Range
	 */
	public function setYear(int $set): self {
		if ($set < 0) {
			throw new Exception_Range(map('Date::setYear({value})', ['value' => $set]));
		}
		if ($this->year !== $set) {
			$this->_weekday = $this->_year_day = null;
		}
		$this->year = $set;
		return $this;
	}

	/**
	 * @return int|null
	 */
	protected function _weekday(): ?int {
		if (($this->_weekday === null) && (!$this->_refresh())) {
			return null;
		}
		return $this->_weekday;
	}

	/**
	 * @param integer $set
	 * @return ?int
	 */
	public function weekday(int $set = null): ?int {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setWeekday(intval($set));
		}
		return $this->_weekday();
	}

	/**
	 * Sets the DOW to the selected one
	 *
	 * @param integer $set
	 * @return $this
	 */
	public function setWeekday(int $set) {
		$set = abs($set) % 7;
		$weekday = $this->_weekday();
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
	 * Get the 0-based index of the day of the year
	 *
	 * @inline_test zesk\Date::factory('2020-01-01')->year_day() === 0
	 * @inline_test zesk\Date::factory('2020-01-02')->year_day() === 1
	 * @param integer $set
	 * @return int|null
	 */
	public function yearday(): ?int {
		if (($this->_year_day === null) && (!$this->_refresh())) {
			return null;
		}
		return $this->_year_day;
	}

	/**
	 * Get the 0-based index of the day of the year
	 *
	 * @inline_test zesk\Date::factory('2020-01-01')->year_day() === 0
	 * @inline_test zesk\Date::factory('2020-01-02')->year_day() === 1
	 * @param integer $set
	 * @return self|integer
	 */
	public function setYearday(int $set) {
		$yearday = $this->yearDay();
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Returns the last day of the month (or number of days in the month)
	 *
	 * @return integer
	 * @see self::days_in_month
	 */
	public function lastDayOfMonth() {
		return self::days_in_month($this->month, $this->year);
	}

	/**
	 * Return the number of days in the month
	 * @param int $month
	 * @param int $year
	 * @return int
	 * @throws Exception_Range
	 */
	public static function daysInMonth(int $month, int $year): int {
		$daysInMonth = [
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
		];
		if ($month < 1 || $month > 12) {
			throw new Exception_Range('Month between 1 and 12');
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
	public function compare(Date $value): int {
		try {
			$result = $this->year - $value->_year();
			if ($result === 0) {
				$result = $this->month - $value->_month();
				if ($result === 0) {
					$result = $this->day - $value->day();
				}
			}
			return $result;
		} catch (Exception_Range $e) {
			return 0;
		}
	}

	/**
	 * Returns true if $date is before $this
	 *
	 * @param Date $date
	 * @param boolean $equal
	 *            Returns true if $date === $this
	 * @return boolean
	 */
	public function before(Date $date, bool $equal = false): bool {
		$result = $this->compare($date);
		if ($equal) {
			return $result <= 0;
		} else {
			return $result < 0;
		}
	}

	/**
	 * Returns true if $date is after $this
	 *
	 * @param Date $date
	 * @param boolean $equal
	 *            Returns true if $date === $this
	 * @return boolean
	 */
	public function after(Date $date, bool $equal = false): bool {
		$result = $this->compare($date);
		if ($equal) {
			return $result >= 0;
		} else {
			return $result > 0;
		}
	}

	/**
	 * Returns difference in seconds between two dates
	 *
	 * @param Date $value
	 * @return int
	 */
	public function subtract(Date $value): int {
		try {
			return $this->unixTimestamp() - $value->unixTimestamp();
		} catch (\Exception $e) {
			return PHP_INT_MAX;
		}
	}

	/**
	 * Returns difference in seconds between two dates
	 *
	 * @param Date $value
	 * @return float
	 */
	public function subtractDays(Date $value): float {
		return round($this->subtract($value) / self::seconds_in_day);
	}

	/**
	 * @param Date|null $min_date
	 * @param Date|null $max_date
	 * @return bool
	 */
	public function clamp(Date $min_date = null, Date $max_date = null): bool {
		if ($min_date && $this->before($min_date)) {
			$this->setDate($min_date);
			return true;
		}
		if ($max_date && $this->after($max_date)) {
			$this->setDate($max_date);
			return true;
		}
		return false;
	}

	/**
	 * Add years, months, or days to a Date
	 * @param int $years
	 * @param int $months
	 * @param int $days
	 * @return $this
	 * @throws Exception_Range
	 */
	public function add(int $years = 0, int $months = 0, int $days = 0): self {
		$foo = mktime(0, 0, 0, $this->month + $months, $this->day + $days, $this->year + $years);

		return $this->_setDateArray(getdate($foo));
	}

	/**
	 * Add units to a date or time
	 *
	 * @param integer $n_units
	 * @param string $units Use Date::UNIT_FOO for units
	 * @return Date|null
	 * @throws Exception_Deprecated
	 * @throws Exception_Parameter
	 */
	public function addUnit(int $n_units = 1, string $units = self::UNIT_DAY): self {
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
			[$n_units, $units] = [
				$units,
				$n_units,
			];
			zesk()->deprecated('{method} called with {n_units} {units} first', [
				'method' => __METHOD__,
				'n_units' => $n_units,
				'units' => $units,
			]);
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
				throw new Exception_Parameter('{method)({n_units}, {units}): Invalid unit', [
					'method' => __METHOD__,
					'n_units' => $n_units,
					'units' => $units,
				]);
		}
	}

	/**
	 * Month names in English
	 *
	 * @var array
	 */
	private static $months = [
		1 => 'January',
		'February',
		'March',
		'April',
		'May',
		'June',
		'July',
		'August',
		'September',
		'October',
		'November',
		'December',
	];

	/**
	 * Month names translated
	 *
	 * @var array
	 */
	private static $translated_months = [];

	/**
	 * Weekday names in English
	 *
	 * @var array
	 */
	private static $weekday_names = [
		0 => 'Sunday',
		1 => 'Monday',
		2 => 'Tuesday',
		3 => 'Wednesday',
		4 => 'Thursday',
		5 => 'Friday',
		6 => 'Saturday',
	];

	/**
	 * Weekday names translated
	 *
	 * @var array
	 */
	private static $translated_weekdays = [];

	/**
	 * Return a localized list of month dates, 1-index based
	 *
	 * @param Locale $locale Locale to translate to
	 * @param boolean $short Short dates
	 * @return array
	 */
	public static function month_names(Locale $locale, $short = false) {
		$id = $locale->id();
		$short = intval(boolval($short));
		if (isset(self::$translated_months[$id][$short])) {
			return self::$translated_months[$id][$short];
		}
		$locale_months = [];
		foreach (self::$months as $i => $month) {
			$locale_months[$i] = $locale($short ? 'Date-short:=' . substr($month, 0, 3) : 'Date:=' . $month);
		}
		self::$translated_months[$id][$short] = $locale_months;
		return $locale_months;
	}

	/**
	 *
	 * @param Locale $locale
	 * @param boolean $short
	 * @return string[]
	 */
	public static function weekday_names(Locale $locale, $short = false) {
		$id = $locale->id();
		$short = intval(boolval($short));
		if (isset(self::$translated_weekdays[$id][$short])) {
			return self::$translated_weekdays[$id][$short];
		}
		$locale_weekdays = [];
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
	private function _ymd_format($sep = '-') {
		return $this->year . $sep . StringTools::zero_pad($this->month) . $sep . StringTools::zero_pad($this->day);
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
	public function formatting(Locale $locale = null, array $options = []) {
		// $old_locale = setlocale(LC_TIME,0);
		// setlocale(LC_TIME, $locale);
		// $ts = $this->toTimestamp();
		$m = $this->month;
		$w = $this->weekday();
		$d = $this->day;

		$x = [];
		$x['M'] = $m;
		$x['D'] = $this->day;
		$x['W'] = $w;

		foreach ($x as $k => $v) {
			$x[$k . $k] = StringTools::zero_pad($v, 2);
		}

		$x['YYYY'] = $y = strval($this->year);
		$x['YY'] = substr($y, -2);

		if ($locale) {
			$x['MMMM'] = $this->month_names($locale)[$m] ?? '?';
			$x['MMM'] = $this->month_names($locale, true)[$m] ?? '?';

			$x['DDD'] = $locale->ordinal($d);

			if ($w !== null) {
				$x['WWWW'] = $this->weekday_names($locale)[$w] ?? '?';
				$x['WWW'] = $this->weekday_names($locale, true)[$w] ?? '?';
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
	public function format(Locale $locale = null, $format_string = null, array $options = []) {
		if ($format_string === null) {
			$format_string = self::$default_format_string;
		}
		$formatting = $this->formatting($locale, $options);
		return map($format_string, $formatting);
	}

	/**
	 * Set/get date as an integer (UNIX timestamp)
	 *
	 * @param integer $set
	 * @return $this|int
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Range
	 */
	public function integer($set = null): int {
		return $this->unix_timestamp($set);
	}

	/**
	 * @param Date $d
	 * @return bool
	 */
	public function equals(Date $d): bool {
		return $d->__toString() === $this->__toString();
	}

	/**
	 * Are the years equal in these two dates?
	 *
	 * @param Date $d
	 * @return bool
	 * @since 2022-01
	 */
	public function equalYears(Date $d): bool {
		return $d->_year() === $this->year;
	}

	/**
	 * Are the months equal in these two dates?
	 *
	 * @param Date $d
	 * @return bool
	 * @since 2022-01
	 */
	public function equalMonths(Date $d): bool {
		return $d->_month() === $this->month;
	}

	/**
	 * Are the months equal in these two dates?
	 *
	 * @param Date $d
	 * @return bool
	 * @deprecated 2022-01
	 */
	public function equal_months(Date $d): bool {
		zesk()->deprecated('old style');
		return $d->equalMonths($d);
	}

	/**
	 * @return boolean
	 */
	public function is_last_day_of_month() {
		return ($this->day === $this->days_in_month($this->month, $this->year));
	}

	/**
	 * Check if empty, return false. Otherwise compute _weekday and _yearday and return true
	 *
	 * @return bool
	 * @todo gmmktime? UTC
	 */
	private function _refresh(): bool {
		if ($this->isEmpty()) {
			return false;
		}
		$date = getdate(mktime(0, 0, 0, $this->month, $this->day, $this->year));
		$this->_weekday = $date['wday'];
		$this->_year_day = $date['yday'];
		return true;
	}

	/**
	 * Is the supplied year (or the date's year) a leap year?
	 *
	 * @param ?int $year
	 * @return boolean
	 * @since 2022-01
	 */
	public function isLeapYear(int $year = null): bool {
		if ($year === null) {
			if ($this->isEmpty()) {
				return false;
			}
			$year = $this->year;
		}
		return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
	}

	/**
	 * @return int
	 */
	private static function _gregorian_offset(): int {
		if (self::$gregorian_offset === null) {
			self::$gregorian_offset = \gregoriantojd(1, 1, 1) - 1;
		}
		return self::$gregorian_offset;
	}

	/**
	 * Get or set the gregorgian offset from year 1
	 *
	 * @param integer $set
	 * @return $this|int|null
	 * @throws Exception_Range
	 */
	public function gregorian(int $set = null): int {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setGregorian($set);
		}
		return \gregoriantojd($this->month, $this->day, $this->year) - self::_gregorian_offset();
	}

	/**
	 * Get or set the gregorgian offset from year 1
	 *
	 * @param int $set
	 * @return self
	 * @since 2022-01
	 */
	public function setGregorian(int $set): self {
		[$month, $day, $year] = explode('/', \jdtogregorian($set + self::_gregorian_offset()), 3);

		try {
			return $this->ymd(intval($year), intval($month), intval($day));
		} catch (Exception_Range $e) {
			zesk()->logger->critical("jdtogregorian returned date with invalid range ($set) go:" . self::_gregorian_offset());
			return $this;
		}
	}

	/**
	 * Returns the last day of the month (or number of days in the month).
	 *
	 * @return integer
	 * @deprecated 2020-10
	 * @see Date::last_day_of_month()
	 * @see Date::days_in_month
	 */
	public function lastday() {
		return self::last_day_of_month();
	}

	/**
	 * Is this date empty? e.g.
	 * not set to any value
	 *
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_empty() {
		zesk()->deprecated('old naming');
		return $this->isEmpty();
	}

	/**
	 * Get the 0-based index of the day of the year
	 *
	 * @inline_test zesk\Date::factory('2020-01-01')->year_day() === 0
	 * @inline_test zesk\Date::factory('2020-01-02')->year_day() === 1
	 * @param integer $set
	 * @return ?int
	 * @deprecated 2022-01
	 */
	public function year_day(int $set = null): ?int {
		zesk()->deprecated('yearDay instead');
		if ($set !== null) {
			return $this->setYearDay($set);
		}
		return $this->yearDay();
	}

	/**
	 *
	 * @param integer $year
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_leap_year(int $year = null): bool {
		zesk()->deprecated('old style');
		return $this->isLeapYear($year);
	}

	/**
	 * @param $set
	 * @return mixed
	 * @throws Exception_Convert
	 * @throws Exception_Deprecated
	 * @throws Exception_Parameter
	 * @throws Exception_Range
	 * @deprecated 2022-01
	 */
	public function unix_timestamp($set = null) {
		if ($set !== null) {
			if (!is_numeric($set)) {
				throw new Exception_Parameter('Date::unix_timestamp({0}): Invalid unix timestamp (numeric)', $set);
			}
			$this->setUNIXTimestamp(intval($set));
			zesk()->deprecated('setter');
		}
		return $this->unixTimestamp();
	}

	/**
	 * Returns difference in seconds between two dates
	 *
	 * @param Date $value
	 * @return integer
	 * @deprecated 2022-01
	 */
	public function subtract_days(Date $value): float {
		zesk()->deprecated('old style');
		return $this->subtractDays($value);
	}

	/**
	 * @param int $n_units
	 * @param string $units
	 * @return $this
	 * @throws Exception_Deprecated
	 * @throws Exception_Parameter
	 * @deprecated 2022-01
	 */
	public function add_unit(int $n_units = 1, string $units = self::UNIT_DAY): self {
		zesk()->deprecated('old style');
		return $this->addUnit($n_units, $units);
	}

	/**
	 * Returns the last day of the month (or number of days in the month)
	 *
	 * @return integer
	 * @see self::days_in_month
	 * @deprecated 2022-01
	 */
	public function last_day_of_month() {
		zesk()->deprecated('old style');
		return $this->lastDayOfMonth();
	}

	/**
	 * @param integer $month
	 * @param integer $year
	 * @return integer
	 * @deprecated 2022-01
	 */
	public static function days_in_month(int $month, int $year): int {
		zesk()->deprecated('old style');
		return self::daysInMonth($month, $year);
	}

	/**
	 * Make this Date value empty
	 *
	 * @return Date
	 * @deprecated 2022-01
	 */
	public function set_empty() {
		zesk()->deprecated('old style');
		return $this->setEmpty();
	}

	/**
	 * Are the years equal in these two dates?
	 *
	 * @param Date $d
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function equal_years(Date $d) {
		zesk()->deprecated('old style');
		return $this->equalYears($d);
	}
}
