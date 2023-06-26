<?php
declare(strict_types=1);

/**
 * Date
 * @author kent
 * @package zesk
 * @subpackage model
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @see Database,DateTime,Time
 */

namespace zesk;

use DateTime;
use OutOfBoundsException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Locale\Locale;
use function gregoriantojd;
use function jdtogregorian;

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
	 * Construct a new date instance with specific Year/Month/Day
	 * Pass a negative value to use current year/month/date
	 *
	 * @param int|null $year
	 * @param int|null $month
	 * @param int|null $day
	 * @return Date
	 * @throws OutOfBoundsException
	 */
	public static function instance(int $year = null, int $month = null, int $day = null): Date {
		$d = new Date();
		$d->ymd($year, $month, $day);
		return $d;
	}

	/**
	 * @param mixed $value
	 * @return Date
	 */
	public static function factory(null|int|Date|Timestamp|DateTime $value = null): self {
		return new self($value);
	}

	/**
	 * Date constructor.
	 */
	public function __construct(null|int|Date|Timestamp|DateTime $value = null) {
		$this->_weekday = null;
		$this->_year_day = null;
		$this->set($value);
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
		return (new Date())->setNow();
	}

	/**
	 * Return a new Date object with the current date
	 *
	 * @return Date
	 */
	public static function utcNow(): self {
		return (new Date())->setUTCNow();
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
		$this->_weekday = null;
		$this->_year_day = null;
		return $this;
	}

	/**
	 * @param DateTime $value
	 * @return $this
	 */
	public function setDateTime(DateTime $value): self {
		return $this->setUNIXTimestamp($value->getTimestamp());
	}

	/**
	 * Set the date with a variety of values
	 * @param mixed $value
	 * @return $this
	 */
	public function set(null|int|Date|Timestamp|DateTime $value): self {
		if ($value === null) {
			return $this->setEmpty();
		}
		if (is_int($value)) {
			return $this->setUNIXTimestamp($value);
		}
		return match ($value::class) {
			Date::class, Timestamp::class => $this->setUNIXTimestamp($value->unixTimestamp()),
			DateTime::class => $this->setDateTime($value),
		};
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
		if ($this->isEmpty()) {
			return '';
		}
		return $this->year . '-' . StringTools::zeroPad($this->month) . '-' . StringTools::zeroPad($this->day);
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
	 * @throws ParseException
	 */

	/**
	 * @param string $value
	 * @return self
	 * @throws ParseException
	 * @throws ParseException
	 */
	public function parse(string $value): self {
		$ts = @strtotime($value, $this->unixTimestamp());
		if ($ts < 0 || $ts === false) {
			throw new ParseException('Date::fromString({value})', ['value' => $value]);
		}
		return $this->setUNIXTimestamp($ts);
	}

	/**
	 * @return $this
	 */
	public function setNow(): self {
		try {
			return $this->_setDateArray(getdate());
		} catch (OutOfBoundsException $e) {
			PHP::log($e);
			return $this;
		}
	}

	/**
	 * @return $this
	 */
	public function setUTCNow(): self {
		return $this->setUNIXTimestamp(time());
	}

	/**
	 * @return int
	 */
	public function unixTimestamp(): int {
		$ts = gmmktime(0, 0, 0, $this->month, $this->day, $this->year);
		/* returns false, but have not been able to get it to do so with any ints used so f that */
		return intval($ts);
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function setUNIXTimestamp(int $set): self {
		return $this->_setDateArray(getdate($set));
	}

	/**
	 * @param null|int $yy
	 * @param null|int $mm
	 * @param null|int $dd
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function ymd(int $yy = null, int $mm = null, int $dd = null): self {
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
	 * @throws OutOfBoundsException
	 */
	private function _setDateArray(array $date): self {
		$this->ymd($date['year'], $date['mon'], $date['mday']);
		return $this;
	}

	/**
	 * @return int
	 */
	public function month(): int {
		return $this->month;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function setMonth(int $set): self {
		if ($set < 1 || $set > 12) {
			throw new OutOfBoundsException(ArrayTools::map('Date::setMonth({set})', ['set' => $set]));
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
	 * @return int
	 */
	public function quarter(): int {
		return intval(($this->month - 1) / 3) + 1;
	}

	/**
	 * Set or get 1-based quarter number
	 *
	 * @param int $set Set 1-based quarter (1,2,3,4)
	 * @return self
	 * @throws OutOfBoundsException
	 */
	public function setQuarter(int $set): self {
		if ($set < 1 || $set > 4) {
			throw new OutOfBoundsException(ArrayTools::map('Date::quarter({set})', ['set' => $set]));
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
	 * @return integer
	 */
	public function day(): int {
		return $this->day;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function setDay(int $set): self {
		if ($set < 1 || $set > 31) {
			throw new OutOfBoundsException("Date::day($set)");
		}
		if ($this->day !== $set) {
			$this->_weekday = $this->_year_day = null;
		}
		$this->day = $set;
		return $this;
	}

	/**
	 *
	 * @return int
	 */
	public function year(): int {
		return $this->year;
	}

	/**
	 * @param int $set
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function setYear(int $set): self {
		if ($set < 0) {
			throw new OutOfBoundsException(ArrayTools::map('Date::setYear({value})', ['value' => $set]));
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
	public function weekday(): ?int {
		if (($this->_weekday === null) && (!$this->_refresh())) {
			return null;
		}
		return $this->_weekday;
	}

	/**
	 * Sets the DOW to the selected one
	 *
	 * @param int $set
	 * @return $this
	 */
	public function setWeekday(int $set): self {
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
	 * Get the 0-based index of the day of the year
	 *
	 * @inline_test zesk\Date::factory('2020-01-01')->year_day() === 0
	 * @inline_test zesk\Date::factory('2020-01-02')->year_day() === 1
	 * @return int|null
	 * @see Date_Test::test_yearDay()
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
	 * @param int $set
	 * @return self
	 * @throws SemanticsException
	 * @see Date_Test::test_yearDay()
	 */
	public function setYearday(int $set): self {
		$yearday = $this->yearDay();
		if ($yearday === null) {
			throw new SemanticsException('Empty date, can not {method}', ['method' => __METHOD__]);
		}
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Returns the last day of the month (or number of days in the month)
	 *
	 * @return integer
	 * @see self::days_in_month
	 */
	public function lastDayOfMonth(): int {
		return self::daysInMonth($this->month, $this->year);
	}

	/**
	 * Return the number of days in the month
	 * @param int $month
	 * @param int $year
	 * @return int
	 * @throws OutOfBoundsException
	 */
	public static function daysInMonth(int $month, int $year): int {
		$daysInMonth = [
			1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30,
			12 => 31,
		];
		if ($month < 1 || $month > 12) {
			throw new OutOfBoundsException('Month between 1 and 12');
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
		return $this->unixTimestamp() - $value->unixTimestamp();
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
	 */
	public function add(int $years = 0, int $months = 0, int $days = 0): self {
		$foo = mktime(0, 0, 0, $this->month + $months, $this->day + $days, $this->year + $years);

		return $this->_setDateArray(getdate($foo));
	}

	/**
	 * Add units to a date or time
	 *
	 * @param int $n_units
	 * @param string $units Use Date::UNIT_FOO for units
	 * @return self
	 * @throws ParameterException
	 */
	public function addUnit(int $n_units = 1, string $units = self::UNIT_DAY): self {
		return match ($units) {
			self::UNIT_WEEKDAY, self::UNIT_DAY => $this->add(0, 0, $n_units),
			self::UNIT_WEEK => $this->add(0, 0, $n_units * self::DAYS_PER_WEEK),
			self::UNIT_MONTH => $this->add(0, $n_units),
			self::UNIT_QUARTER => $this->add(0, $n_units * self::MONTHS_PER_QUARTER),
			self::UNIT_YEAR => $this->add($n_units),
			default => throw new ParameterException('{method)({n_units}, {units}): Invalid unit', [
				'method' => __METHOD__, 'n_units' => $n_units, 'units' => $units,
			]),
		};
	}

	/**
	 * Month names in English
	 *
	 * @var array
	 */
	private static array $months = [
		1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July',
		8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
	];

	/**
	 * Month names translated
	 *
	 * @var array
	 */
	private static array $translated_months = [];

	/**
	 * Weekday names in English
	 *
	 * @var array
	 */
	private static array $weekday_names = [
		0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
	];

	/**
	 * Weekday names translated
	 *
	 * @var array
	 */
	private static array $translated_weekdays = [];

	/**
	 * @param Locale $locale
	 * @param bool $short
	 * @param array $source
	 * @param array $cached
	 * @return array
	 */
	private static function _cachedNames(Locale $locale, bool $short, array $source, array &$cached): array {
		$id = $locale->id();
		$short = intval($short);
		if (isset($cached[$id][$short])) {
			return $cached[$id][$short];
		}
		$names = [];
		foreach ($source as $i => $name) {
			$names[$i] = $locale->__($short ? 'Date-short:=' . substr($name, 0, 3) : 'Date:=' . $name);
		}
		$cached[$id][$short] = $names;
		return $names;
	}

	/**
	 * Return a localized list of month dates, 1-index based
	 *
	 * @param Locale $locale Locale to translate to
	 * @param boolean $short Short dates
	 * @return array
	 */
	public static function monthNames(Locale $locale, bool $short = false): array {
		return self::_cachedNames($locale, $short, self::$months, self::$translated_months);
	}

	/**
	 *
	 * @param Locale $locale
	 * @param boolean $short
	 * @return string[]
	 */
	public static function weekdayNames(Locale $locale, bool $short = false): array {
		return self::_cachedNames($locale, $short, self::$weekday_names, self::$translated_weekdays);
	}

	/**
	 * Format YYYY${sep}MM${sep}DD
	 *
	 * @return string
	 */
	private function _ymd_format(): string {
		return $this->year . '-' . StringTools::zeroPad($this->month) . '-' . StringTools::zeroPad($this->day);
	}

	/**
	 * @return string
	 */
	public function sql(): string {
		return $this->_ymd_format();
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Temporal::formatting()
	 */
	public function formatting(array $options = []): array {
		$locale = $options['locale'] ?? null;
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
			$x[$k . $k] = StringTools::zeroPad($v);
		}

		$x['YYYY'] = $y = strval($this->year);
		$x['YY'] = substr($y, -2);

		if ($locale instanceof Locale) {
			$x['MMMM'] = $this->monthNames($locale)[$m] ?? '?';
			$x['MMM'] = $this->monthNames($locale, true)[$m] ?? '?';

			$x['DDD'] = $locale->ordinal($d);

			if ($w !== null) {
				$x['WWWW'] = $this->weekdayNames($locale)[$w] ?? '?';
				$x['WWW'] = $this->weekdayNames($locale, true)[$w] ?? '?';
			}
		}
		return $x;
	}

	/**
	 *
	 * @param string $format
	 * @param array $options
	 * @return string
	 */
	public function format(string $format = '', array $options = []): string {
		if ($format === '') {
			$format = self::DEFAULT_FORMAT_STRING;
		}
		$formatting = $this->formatting($options);
		return ArrayTools::map($format, $formatting);
	}

	/**
	 * Set/get date as an integer (UNIX timestamp)
	 *
	 * @return int
	 */
	public function integer(): int {
		return $this->unixTimestamp();
	}

	/**
	 * @param int $value
	 * @return $this
	 */
	public function setInteger(int $value): self {
		return $this->setUNIXTimestamp($value);
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
		return $d->year() === $this->year;
	}

	/**
	 * Are the months equal in these two dates?
	 *
	 * @param Date $d
	 * @return bool
	 * @since 2022-01
	 */
	public function equalMonths(Date $d): bool {
		return $d->month() === $this->month;
	}

	/**
	 * @return boolean
	 */
	public function isLastDayOfMonth(): bool {
		return ($this->day === $this->daysInMonth($this->month, $this->year));
	}

	/**
	 * Check if empty, return false. Otherwise, compute _weekday and _yearday and return true
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
			self::$gregorian_offset = gregoriantojd(1, 1, 1) - 1;
		}
		return self::$gregorian_offset;
	}

	/**
	 * Get or set the gregorgian offset from year 1
	 *
	 * @return int
	 */
	public function gregorian(): int {
		return gregoriantojd($this->month, $this->day, $this->year) - self::_gregorian_offset();
	}

	/**
	 * Get or set the gregorgian offset from year 1
	 *
	 * @param int $set
	 * @return self
	 * @since 2022-01
	 */
	public function setGregorian(int $set): self {
		[$month, $day, $year] = explode('/', jdtogregorian($set + self::_gregorian_offset()), 3);

		try {
			return $this->ymd(intval($year), intval($month), intval($day));
		} catch (OutOfBoundsException) {
			PHP::log("jdtogregorian returned date with invalid range ($set) go:" . self::_gregorian_offset());
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
	public function lastDay(): int {
		return self::lastDayOfMonth();
	}
}
