<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use DateTimeInterface;
use DateTimeZone;
use DateTime;
use DateInterval;
use OutOfBoundsException;

/**
 * Timestamp class is similar to PHP DateTime, it used to be called DateTime, and ZDateTime.
 *
 * Changed in 2014-02-26 to inherit from DateTime to deal with pesky timezone issues.
 * Changed in 2015-04-27 to inherit from Temporal to support universal formatting
 *
 * @author kent
 */
class Timestamp extends Temporal {
	/**
	 * Default __toString format
	 *
	 * Override by setting global [__CLASS__,"format_string"]
	 *
	 * @var string
	 */
	public const DEFAULT_FORMAT_STRING = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}';

	public const FORMAT_JSON = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {ZZZ}';

	/**
	 * Set up upon load
	 *
	 * @var string
	 */
	private static string $default_format_string = self::DEFAULT_FORMAT_STRING;

	/**
	 * https://en.wikipedia.org/wiki/Year_2038_problem
	 *
	 * @var integer
	 */
	public const maximum_year = 2038;

	/**
	 *
	 * @var ?DateTime
	 */
	protected ?DateTime $datetime = null;

	/**
	 *
	 * @var DateTimeZone
	 */
	protected DateTimeZone $tz;

	/**
	 *
	 * @var integer
	 */
	protected int $milliseconds = 0;

	/**
	 * Internal year format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_YEAR = 'Y';

	/**
	 * Internal month format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_MONTH = 'n';

	/**
	 * Internal day format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_DAY = 'j';

	/**
	 * Internal hour format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_HOUR = 'G';

	/**
	 * Internal minute format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_MINUTE = 'i';

	/**
	 * Internal second format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_SECOND = 's';

	/**
	 * Internal weekday format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_WEEKDAY = 'w';

	/**
	 * Internal yearday format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_YEARDAY = 'z';

	/**
	 * Add global configuration
	 *
	 * @param Application $app
	 */
	public static function hooks(Application $app): void {
		$app->hooks->add(Hooks::HOOK_CONFIGURED, [__CLASS__, 'configured', ]);
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		self::$default_format_string = $application->configuration->getPath([
			__CLASS__, 'format_string',
		], self::DEFAULT_FORMAT_STRING);
	}

	/**
	 *
	 * @return DateTimeZone
	 */
	public static function timezone_utc(): DateTimeZone {
		static $utc = null;
		if (!$utc) {
			$utc = new DateTimeZone('UTC');
		}
		return $utc;
	}

	/**
	 * @return DateTimeZone
	 */
	public static function timezone_local(): DateTimeZone {
		return new DateTimeZone(date_default_timezone_get());
	}

	/**
	 * Construct a new Timestamp consisting of a Date and a Time
	 *
	 * @param null|DateTimeInterface|int|string|Time|Date|Timestamp $value
	 * @param DateTimeZone|null $timezone
	 * @throws Exception_Convert
	 */
	public function __construct(null|DateTimeInterface|int|string|Time|Date|Timestamp $value = '', DateTimeZone $timezone = null) {
		$this->milliseconds = 0;
		if ($value instanceof DateTimeInterface) {
			$this->tz = $value->getTimezone();

			try {
				$this->datetime = new DateTime('now', $this->tz);
			} catch (\Exception) {
			}
			$this->setUnixTimestamp($value->getTimestamp());
		} else {
			$this->tz = $timezone === null ? self::timezone_local() : $timezone;
			if ($value !== null && $value !== '0000-00-00 00:00:00' && $value !== '0000-00-00') {
				try {
					$this->datetime = new DateTime('now', $this->tz);
				} catch (\Exception) {
				}
			}
			$this->set($value);
		}
	}

	/**
	 */
	public function __clone(): void {
		if ($this->datetime) {
			$this->datetime = clone $this->datetime;
		}
	}

	/**
	 * Create a duplicate object
	 *
	 * @return Timestamp
	 */
	public function duplicate(): self {
		return clone $this;
	}

	/**
	 * Get time zone
	 *
	 * @return DateTimeZone
	 */
	public function timeZone(): DateTimeZone {
		return $this->tz;
	}

	/**
	 * Set time zone
	 *
	 * @param DateTimeZone|string $mixed
	 * @return $this
	 */
	public function setTimeZone(DateTimeZone|string $mixed): self {
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
	 * @param string|int|Date|Time|Timestamp|null $value
	 * @param string|DateTimeZone|null $timezone
	 * @return static
	 * @throws Exception_Convert
	 */
	public static function factory(null|string|int|Date|Time|Timestamp $value, string|DateTimeZone $timezone = null): self {
		if (!$timezone instanceof DateTimeZone) {
			$timezone = empty($timezone) ? null : new DateTimeZone($timezone);
		}
		return new self($value, $timezone);
	}

	/**
	 * Return new Timestamp date time representing now
	 *
	 * @param string|DateTimeZone|null $timezone
	 * @return Timestamp
	 */
	public static function now(string|DateTimeZone $timezone = null): self {
		return self::factory('now', $timezone);
	}

	/**
	 * Just prefer utc as an acronym.
	 * Returns UTC Timestamp set to current time.
	 *
	 * @return Timestamp
	 */
	public static function utc_now(): self {
		return self::factory('now', self::timezone_utc());
	}

	/**
	 * @return $this
	 */
	public function setNow(): self {
		$this->setUnixTimestamp(time());
		return $this;
	}

	/**
	 * Set/get the date component of this Timestamp
	 *
	 * @return Date date portion of Timestamp
	 */
	public function date(): Date {
		return Date::instance($this->year(), $this->month(), $this->day());
	}

	/**
	 * Set/get the date component of this Timestamp
	 *
	 * @param Date $date
	 * @return self
	 */
	public function setDate(Date $date): self {
		$this->ymd($date->year(), $date->month(), $date->day());
		return $this;
	}

	/**
	 * Set/get the time component of this Timestamp
	 *
	 * @return Time Timestamp
	 */
	public function time(): Time {
		return Time::instance($this->hour(), $this->minute(), $this->second());
	}

	/**
	 * Set/get the time component of this Timestamp
	 *
	 * @param Time $time
	 * @return self
	 */
	public function setTime(Time $time): self {
		$this->hms($time->hour(), $time->minute(), $time->second());
		return $this;
	}

	/**
	 * Get the integer value of this Timestamp
	 *
	 * @see Timestamp::unixTimestamp()
	 */
	public function integer(): int {
		return $this->unixTimestamp();
	}

	/**
	 * Check if this object is empty, or unset
	 *
	 * @return boolean
	 */
	public function isEmpty(): bool {
		return $this->datetime === null;
	}

	/**
	 * Set this Timetamp to empty
	 *
	 * @return Timestamp
	 */
	public function setEmpty(): self {
		$this->datetime = null;
		return $this;
	}

	/**
	 * Set the Timestamp with a variety of formats
	 *
	 * @param null|string|int|Date|Time|Timestamp $value
	 * @return $this
	 * @throws Exception_Convert
	 */
	public function set(null|string|int|Date|Time|Timestamp $value): self {
		if (empty($value)) {
			$this->setEmpty();
			return $this;
		}
		if (is_string($value)) {
			return $this->parse($value);
		}
		if (is_numeric($value)) {
			return $this->setUnixTimestamp($value);
		}
		if ($value instanceof Date) {
			$this->setDate($value);
			return $this;
		}
		if ($value instanceof Time) {
			$this->setTime($value);
			return $this;
		}
		assert($value instanceof Timestamp);
		return $this->setUnixTimestamp($value->unixTimestamp());
	}

	/**
	 * Convert to a standard string, suitable for use in databases and for string comparisons
	 *
	 * @return string
	 */
	public function __toString(): string {
		if ($this->isEmpty()) {
			return '';
		}
		return $this->format();
	}

	/**
	 * Convert to a standard string, suitable for use in databases and for string comparisons
	 *
	 * @return string
	 */
	public function json(): string {
		return $this->format(null, self::FORMAT_JSON);
	}

	/**
	 * Require object
	 *
	 * @return DateTime
	 */
	private function _datetime(): DateTime {
		if ($this->datetime === null) {
			$this->datetime = new DateTime('now', $this->tz);
		}
		return $this->datetime;
	}

	/**
	 * Retrieve the DateTime
	 *
	 * @return ?DateTime
	 */
	public function datetime(): ?DateTime {
		return $this->datetime;
	}

	/**
	 *
	 * @return int
	 */
	public function unixTimestamp(): int {
		return $this->datetime ? $this->datetime->getTimestamp() : -1;
	}

	/**
	 * @param int $set
	 * @return $this
	 */
	public function setUnixTimestamp(int $set): self {
		// 03:14:08 UTC on 19 January 2038 is MAX time using 32-bit integers
		$this->_datetime()->setTimestamp($set);
		return $this;
	}

	/**
	 * was fromLocaleString
	 *
	 * @param string $value
	 * @param string $locale_format
	 * @return boolean
	 * @throws Exception_Convert
	 */
	public function parse_locale_string(string $value, string $locale_format = 'MDY;MD;MY;_'): bool {
		$value = preg_replace('/[^0-9]/', ' ', $value);
		$value = trim(preg_replace('/\s+/', ' ', $value));
		$values = explode(' ', $value);
		$this->setNow();
		if (!is_array($locale_format)) {
			$locale_format = explode(';', $locale_format);
		}
		foreach ($locale_format as $dateParseCodes) {
			$dateParseCodes = str_split($dateParseCodes);
			if (count($values) !== count($dateParseCodes)) {
				continue;
			}
			$this->setMonth(1)->setDay(1);
			foreach ($dateParseCodes as $i => $code) {
				switch (strtoupper($code)) {
					case '_':
						if (strlen($value) == 8) {
							return $this->parse_locale_string(substr($value, 0, 2) . '/' . substr($value, 2, 2) . '/' . substr($value, 4));
						}

						throw new Exception_Convert('Timestamp::parse_locale_string({value},{locale_format}): Unknown format', [
							'value' => $value, 'locale_format' => $locale_format,
						]);
					case 'M':
						$this->setMonth(intval($values[$i]));
						break;
					case 'D':
						$this->setDay(intval($values[$i]));
						break;
					case 'Y':
						$this->setYear(intval($values[$i]));
						break;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * English month names
	 *
	 * @return array
	 */
	private function _month_names_en(): array {
		static $m = [
			'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9,
			'oct' => 10, 'nov' => 11, 'dec' => 12,
		];
		return $m;
	}

	/**
	 * Parse a date string
	 *
	 * @param mixed $value
	 * @return Timestamp
	 * @throws Exception_Convert
	 */
	public function parse(string $value): self {
		// This fails on a cookie date sent by 64-bit systems
		// Set-Cookie: TrkCookieID=51830899; expires=Sat, 16-Aug-2064 04:11:10 GMT
		// DAY, DD-MMM-YYYY HH:MM:SS GMT
		$matches = null;
		$month_names = $this->_month_names_en();
		if (preg_match('/([0-9]{2})-([A-Z]{3})-([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])/i', "$value", $matches)) {
			$mm = $month_names[strtolower($matches[2])] ?? 1;
			$this->ymd(intval($matches[3]), $mm, intval($matches[1]));
			$this->hms(intval($matches[4]), intval($matches[5]), intval($matches[6]));
			return $this;
		}
		$parsed = strtotime($value, time());
		if ($parsed === false) {
			throw new Exception_Convert(map('Timestamp::parse({0})', [$value]));
		}

		try {
			$datetime = new DateTime($value, $this->tz);
		} catch (\Exception $e) {
			PHP::log($e->getMessage());
			$datetime = null;
		}
		$this->datetime = $datetime;
		return $this;
	}

	/**
	 * Get/Set year
	 *
	 * @param string $set
	 * @return Timestamp number
	 */
	public function year($set = null): int {
		if ($set !== null) {
			$this->setYear(intval($set));
			zesk()->deprecated('setter');
		}
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_YEAR)) : -1;
	}

	/**
	 * Get/Set year
	 *
	 * @param string $set
	 * @return Timestamp number
	 */
	public function setYear(int $set): self {
		$this->_datetime()->setDate($set, $this->month(), $this->day());
		return $this;
	}

	/**
	 * Set a 1-based quarter (1,2,3,4)
	 *
	 * @param int $set
	 * @return Timestamp, number
	 */
	public function quarter(mixed $set = null): int {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setQuarter(intval($set));
		}
		return $this->datetime ? intval(($this->month() - 1) * (4 / 12)) + 1 : -1;
	}

	/**
	 * Set a 1-based quarter (1,2,3,4)
	 *
	 * @param int $set
	 * @return Timestamp
	 */
	public function setQuarter(int $set): self {
		if ($set < 1 || $set > 4) {
			throw new OutOfBoundsException("Timestamp::quarter($set)");
		}
		/* $set is 0-3 */
		$quarter = $this->quarter();
		if ($quarter === $set) {
			return $this;
		}
		$this->add(0, ($set - $quarter) * 3);
		return $this;
	}

	/**
	 * Get month
	 *
	 * @return int
	 */
	public function month(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_MONTH)) : -1;
	}

	/**
	 * Get/Set month
	 *
	 * @param int $set
	 * @return Timestamp number
	 * @throws OutOfBoundsException
	 */
	public function setMonth(int $set) {
		if ($set < 1 || $set > 12) {
			throw new OutOfBoundsException("Month must be between 1 and 12 ($set passed)");
		}
		$this->_datetime()->setDate($this->year(), $set, $this->day());
		return $this;
	}

	/**
	 * Get/Set day of month
	 *
	 * @return int
	 */
	public function day(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_DAY)) : -1;
	}

	/**
	 * Set day of month
	 *
	 * @param int $set
	 * @return self
	 */
	public function setDay(int $set): self {
		if ($set < 0 || $set > 31) {
			throw new OutOfBoundsException("Day must be between 1 and 31 ($set passed)");
		}
		$this->_datetime()->setDate($this->year(), $this->month(), $set);
		return $this;
	}

	/**
	 * Is this today?
	 *
	 * @return bool
	 */
	public function today(): bool {
		return $this->datetime->format('Y-m-d') === date('Y-m-d');
	}

	/**
	 * Set date to today
	 *
	 * @return Timestamp
	 */
	public function setToday(): self {
		return $this->setYear(intval(date('Y')))->setMonth(intval(date('n')))->setDay(intval(date('j')));
	}

	/**
	 * Set to the past weekday specified
	 *
	 * @param int $set
	 * @return Timestamp
	 * @throws Exception_Semantics
	 */
	public function setWeekdayPast(int $set): self {
		try {
			return $this->setWeekday($set)->addUnit(-7, self::UNIT_DAY);
		} catch (Exception_Key) {
			return $this;
		}
	}

	/**
	 * Get/set weekday.
	 * Weekday, when set, is always the NEXT possible weekday, including today.
	 *
	 * @return integer
	 */
	public function weekday(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_WEEKDAY)) : -1;
	}

	/**
	 * Get/set weekday.
	 * Weekday, when set, is always the NEXT possible weekday, including today.
	 *
	 * @param int $set
	 * @return Timestamp
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
	 * Get/set yearday
	 *
	 * @return int -1 if empty
	 */
	public function yearday(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_YEARDAY)) : -1;
	}

	/**
	 * Set yearday
	 *
	 * @param int $set
	 * @return self
	 */
	public function setYearday(int $set): self {
		$yearday = $this->yearday();
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Get/set hour of day
	 *
	 * @param int $set
	 * @return self
	 */
	public function hour(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_HOUR)) : -1;
	}

	/**
	 * Get/set hour of day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setHour(int $set): self {
		$this->_datetime()->setTime($set, $this->minute(), $this->second());
		return $this;
	}

	/**
	 * Get minute of the day
	 *
	 * @return int
	 */
	public function minute(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_MINUTE)) : -1;
	}

	/**
	 * Set minute of the day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setMinute(int $set): self {
		$this->_datetime()->setTime($this->hour(), $set, $this->second());
		return $this;
	}

	/**
	 * Get/set second of the day
	 *
	 * @return int
	 */
	public function second(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_SECOND)) : -1;
	}

	/**
	 * Get/set second of the day
	 *
	 * @param int $set
	 * @return Timestamp
	 */
	public function setSecond(int $set): self {
		$this->_datetime()->setTime($this->hour(), $this->minute(), $set);
		return $this;
	}

	/**
	 *
	 * @return integer
	 */
	public function millisecond(): int {
		return $this->datetime ? $this->milliseconds : 0;
	}

	/**
	 *
	 * @return self
	 */
	public function setMillisecond(int $set): self {
		$this->_datetime();
		$this->milliseconds = $set % 1000;
		return $this;
	}

	/**
	 * Number of seconds since midnight
	 *
	 * @return integer
	 */
	public function daySeconds(): int {
		$midnight = clone $this;
		$midnight->midnight();
		return $this->difference($midnight);
	}

	/**
	 * Get 12-hour
	 *
	 * @return int
	 */
	public function hour12(): int {
		if ($this->datetime === null) {
			return -1;
		}
		$hour = $this->hour() % 12;
		return ($hour === 0) ? 12 : $hour;
	}

	/**
	 * Set 12-hour
	 *
	 * @param int $set
	 * @return self
	 */
	public function setHour12(int $set): self {
		$set = $set % 12;
		// Retains AM/PM
		return $this->setHour($set + ($this->hour() < 12 ? 0 : 12));
	}

	/**
	 * Get AMPM
	 */
	public function ampm(): string {
		return $this->time()->ampm();
	}

	/**
	 * Set time to midnight
	 *
	 * @return Timestamp
	 */
	public function midnight(): self {
		$this->_datetime()->setTime(0, 0, 0);
		return $this;
	}

	public function noon(): self {
		$this->_datetime()->setTime(12, 0, 0);
		return $this;
	}

	/**
	 * Set the Year/Month/Date for this Timestamp
	 *
	 * @param ?int $year
	 * @param ?int $month
	 * @param ?int $day
	 * @return Timestamp
	 */
	public function ymd(int $year = null, int $month = null, int $day = null): self {
		$this->_datetime()->setDate($year === null ? $this->year() : $year, $month === null ? $this->month() : $month, $day === null ? $this->day() : $day);
		return $this;
	}

	/**
	 * Set the Hour/Minute/Second for this Timestamp
	 *
	 * @param ?int $hour
	 * @param ?int $minute
	 * @param ?int $second
	 * @return Timestamp
	 */
	public function hms(int $hour = null, int $minute = null, int $second = null): self {
		$this->_datetime()->setTime($hour === null ? $this->hour() : $hour, $minute === null ? $this->minute() : $minute, $second === null ? $this->second() : $second);
		return $this;
	}

	/**
	 * Set the Year/Month/Date/Hour/Minute/Second for this Timestamp
	 *
	 * @param ?int $year
	 * @param ?int $month
	 * @param ?int $day
	 * @param ?int $hour
	 * @param ?int $minute
	 * @param ?int $second
	 * @return Timestamp
	 */
	public function ymdhms(int $year = null, int $month = null, int $day = null, int $hour = null, int $minute = null, int $second = null) {
		return $this->ymd($year, $month, $day)->hms($hour, $minute, $second);
	}

	/**
	 * Compare two Timestamps, like strcmp
	 * $this->compare($value) < 0 ~= ($this < $value) => -
	 * $this->compare($value) < 0 ~= ($this > $value) => +
	 * $this->compare($value) == 0 ~= ($value == $this) => 0
	 *
	 * @param Timestamp $value
	 * @return int
	 */
	public function compare(Timestamp $value): int {
		if ($value->isEmpty()) {
			if ($this->isEmpty()) {
				return 0;
			}
			return 1;
		}
		return $this->unixTimestamp() <=> $value->unixTimestamp();
	}

	/**
	 * Return the difference in seconds between two Timestamps
	 *
	 * @param Timestamp $value
	 * @return integer
	 */
	public function subtract(Timestamp $value): int {
		return $this->unixTimestamp() - $value->unixTimestamp();
	}

	/**
	 * Format a Timestamp in the locale, using a formatting string
	 *
	 * @param string $format_string
	 *            Uses global "Timestamp::format_string" if not specified
	 * @param string $locale
	 *            Locale to use, if any
	 * @return string
	 */
	public function format(Locale $locale = null, string $format_string = '', array $options = []): string {
		if ($format_string === '') {
			$format_string = self::$default_format_string;
		}
		return map($format_string, $this->formatting($locale, $options));
	}

	/**
	 * Formatting a timestamp string
	 *
	 * @param array $options
	 *            'locale' => string. Locale to use, if any
	 *            'unit_minimum' => string. Minimum time unit to display
	 *            'zero_string' => string. What to display when closer to the unit_minimum to the
	 *            time
	 *            'nohook' => boolean. Do not invoke the formatting hook
	 * @return array
	 * @see Locale::now_string
	 * @todo Evaluation global usage
	 * @global string Timestamp::formatting::unit_minumum
	 * @global string Timestamp::formatting::zero_string
	 * @hook Timestamp::formatting
	 */
	public function formatting(Locale $locale = null, array $options = []): array {
		$ts = $this->unixTimestamp();
		$formatting = $this->date()->formatting($locale, $options) + $this->time()->formatting($locale, $options);

		$formatting += ['seconds' => $ts, 'unixTimestamp()' => $ts, 'Z' => '-', 'ZZZ' => '---', ];

		if ($locale) {
			$config_timestamp = $locale->application->configuration->path([__CLASS__, 'formatting', ]);
			$unit_minimum = $options['unit_minimum'] ?? $config_timestamp->get('unit_minumum', '');
			$zero_string = $options['zero_string'] ?? $config_timestamp->get('zero_string', '');
			// Support $unit_minimum and $zero_string strings which include formatting
			$unit_minimum = map($unit_minimum, $formatting);
			$zero_string = map($zero_string, $formatting);

			$formatting['delta'] = $locale->now_string($this, strval($unit_minimum), strval($zero_string));
		}
		if ($this->datetime) {
			// TODO This doesn't actually honor the current locale
			$formatting = ['Z' => $this->datetime->format('e'), 'ZZZ' => $this->datetime->format('T'), ] + $formatting;
		}
		if ($locale && !($options['nohook'] ?? false)) {
			$formatting = $locale->application->hooks->callArguments(__CLASS__ . '::formatting', [
				$this, $locale, $formatting, $options,
			], $formatting);
		}
		return $formatting;
	}

	/**
	 * Are these two timestamps identical?
	 *
	 * @param Timestamp $timestamp
	 * @return boolean
	 */
	public function equals(Timestamp $timestamp): bool {
		if ($timestamp->tz->getName() !== $this->tz->getName()) {
			$timestamp = clone $timestamp;
			$timestamp->setTimeZone($this->tz->getName());
		}
		$options = ['nohook' => true, ];
		return $this->format(null, self::DEFAULT_FORMAT_STRING, $options) === $timestamp->format(null, self::DEFAULT_FORMAT_STRING, $options);
	}

	/**
	 * Is passed in Timestamp before $this?
	 *
	 * @param Timestamp $model
	 * @param boolean $equal
	 *            Return true if they are equal
	 * @return boolean
	 */
	public function before(Timestamp $model, bool $equal = false) {
		$result = $this->compare($model);
		return ($equal) ? ($result <= 0) : ($result < 0);
	}

	/**
	 * Shortcut to test if time is before current time
	 *
	 * @param string $equal
	 *            Return true if time MATCHES current time (seconds)
	 * @return boolean
	 */
	public function beforeNow($equal = false) {
		$timestamp = $this->unixTimestamp();
		$now = time();
		return $equal ? ($timestamp <= $now) : ($timestamp < $now);
	}

	/**
	 * Shortcut to test if time is before current time
	 *
	 * @param bool $equal
	 *            Return true if time MATCHES current time (seconds)
	 * @return bool
	 */
	public function afterNow(bool $equal = false): bool {
		$timestamp = $this->unixTimestamp();
		$now = time();
		return $equal ? ($timestamp >= $now) : ($timestamp > $now);
	}

	/**
	 * Is passed in Timestamp after $this?
	 *
	 * @param Timestamp $model
	 * @param boolean $equal
	 *            Return true if they are equal
	 * @return boolean
	 */
	public function after(Timestamp $model, bool $equal = false): bool {
		$result = $this->compare($model);
		return $equal ? ($result >= 0) : ($result > 0);
	}

	/**
	 * Given another model and this, return the one which is later.
	 * If both are empty, returns $this
	 * If identical, returns $this
	 *
	 * @param ?Timestamp $model
	 * @return Timestamp
	 */
	public function later(Timestamp $model = null): Timestamp {
		if ($model === null) {
			return $this;
		}
		if ($model->isEmpty()) {
			return $this;
		}
		if ($this->isEmpty()) {
			return $model;
		}
		return $model->after($this) ? $model : $this;
	}

	/**
	 * Is this date before current time?
	 *
	 * @return boolean
	 */
	public function isPast() {
		if ($this->isEmpty()) {
			return false;
		}
		return ($this->unixTimestamp() < time());
	}

	/**
	 * Given another model and this, return the one which is earlier.
	 * If both are empty, returns $this
	 * If identical, returns $this
	 *
	 * @param ?Timestamp $model
	 * @return Timestamp
	 */
	public function earlier(Timestamp $model = null): Timestamp {
		if ($model === null) {
			return $this;
		}
		if ($model->isEmpty()) {
			return $this;
		}
		if ($this->isEmpty()) {
			return $model;
		}
		return $model->before($this) ? $model : $this;
	}

	/**
	 * Add DateInterval to dates
	 *
	 * @param DateInterval $interval
	 * @return Timestamp
	 */
	public function addInterval(DateInterval $interval): self {
		$this->datetime->add($interval);
		return $this;
	}

	/**
	 * Add units to dates
	 *
	 * @param int $years
	 * @param int $months
	 * @param int $days
	 * @param int $hours
	 * @param int $minutes
	 * @param int $seconds
	 * @param int $milliseconds
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function add(int $years = 0, int $months = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0, int $milliseconds = 0): self {
		if ($this->datetime === null) {
			throw new Exception_Semantics('Adding to a empty Timestamp');
		}
		$this->_addUnit($years, 'Y')->_addUnit($months, 'M')->_addUnit($days, 'D');
		$this->_addUnit($hours, 'H', true)->_addUnit($minutes, 'M', true)->_addUnit($seconds, 'S', true);
		if ($milliseconds !== 0) {
			$this->milliseconds += $milliseconds;
			if ($this->milliseconds < 0) {
				$seconds = intval($this->milliseconds / 1000) - 1;
				$this->milliseconds = ($this->milliseconds % 1000) + 1000;
				$this->_addUnit($seconds, 'S', true);
			} else {
				$this->_addUnit(intval($this->milliseconds / 1000), 'S', true);
				$this->milliseconds = ($this->milliseconds % 1000);
			}
		}

		return $this;
	}

	/**
	 * Utility for add
	 *
	 * @param int $number
	 * @param string $code
	 * @param boolean $time
	 * @return Timestamp
	 */
	private function _addUnit(int $number, string $code, bool $time = false): self {
		$interval = new DateInterval('P' . ($time ? 'T' : '') . abs($number) . $code);
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
	 *            millisecond, second, minute, hour, day, week, weekday, quarter
	 *              bad unit defaults to year
	 * @param int $precision
	 *            The precision for the result (decimal places to use)
	 * @return int|float
	 */
	public function difference(Timestamp $timestamp, string $unit = self::UNIT_SECOND, int $precision = 0): int|float {
		if ($timestamp->after($this, false)) {
			return -$timestamp->difference($this, $unit, $precision);
		}
		if ($unit === self::UNIT_WEEKDAY) {
			return $this->weekday() - $timestamp->weekday();
		}
		$delta = $this->subtract($timestamp);
		switch ($unit) {
			case self::UNIT_MILLISECOND:
				return $delta * 1000;
			case self::UNIT_SECOND:
				return $delta;
			case self::UNIT_MINUTE:
				return round($delta / 60.0, $precision);
			case self::UNIT_HOUR:
				return round($delta / 3600.0, $precision);
			case self::UNIT_DAY:
				return round($delta / 86400, $precision);
			case self::UNIT_WEEK:
				return round($delta / (86400 * 7), $precision);
		}

		$month_start = $timestamp->month();
		$year_start = $timestamp->year();

		$month_end = $this->month();
		$year_end = $this->year();

		if ($precision === 0) {
			switch ($unit) {
				case self::UNIT_MONTH:
					return ($year_end - $year_start) * 12 + ($month_end - $month_start);
				case self::UNIT_QUARTER:
					$month_end = intval($month_end / 4);
					$month_start = intval($month_start / 4);
					return ($year_end - $year_start) * 4 + ($month_end - $month_start);
				default:
				case self::UNIT_YEAR:
					return ($year_end - $year_start);
			}
		} else {
			// Works like so:
			//
			// 2/22 -> 3/22 = 1 month
			// 2/12 -> 3/22 = 1 month + ((3/22-2/22) / 28)

			$integerMonth = ($year_end - $year_start) * 12 + ($month_end - $month_start);
			$total = Date::daysInMonth($month_start, $year_start);

			$temp = clone $timestamp;
			$temp->setMonth($month_start);
			$temp->setYear($year_start);

			$fract = $temp->subtract($this);
			$fract = $fract / floatval($total * 86400);

			switch ($unit) {
				case self::UNIT_MONTH:
					$result = round($integerMonth + $fract, $precision);

					break;
				case self::UNIT_QUARTER:
					$result = round(($integerMonth + $fract) / 3, $precision);

					break;
				default:
				case self::UNIT_YEAR:
					$result = round(($integerMonth + $fract) / 12, $precision);

					break;
			}
			return $result;
		}
	}

	/**
	 * Set or get a unit.
	 *
	 * @param string $unit
	 *            One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @return self
	 * @throws Exception_Parameter
	 */
	public function unit(string $unit): int {
		switch ($unit) {
			case self::UNIT_MILLISECOND:
				return $this->millisecond();
			case self::UNIT_SECOND:
				return $this->second();
			case self::UNIT_MINUTE:
				return $this->minute();
			case self::UNIT_HOUR:
				return $this->hour();
			case self::UNIT_WEEKDAY:
				return $this->weekday();
			case self::UNIT_DAY:
				return $this->day();
			case self::UNIT_MONTH:
				return $this->month();
			case self::UNIT_QUARTER:
				return $this->quarter();
			case self::UNIT_YEAR:
				return $this->year();
			default:
				throw new Exception_Parameter('Timestamp::unit({unit}): Bad unit', ['unit' => $unit]);
		}
	}

	/**
	 * Set a unit.
	 *
	 * @param string $unit
	 *            One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @param int $value
	 *            Value to set
	 * @return Timestamp integer
	 * @throws Exception_Key|OutOfBoundsException
	 */
	public function setUnit(string $unit, int $value): self {
		return match ($unit) {
			self::UNIT_MILLISECOND => $this->setMillisecond($value),
			self::UNIT_SECOND => $this->setSecond($value),
			self::UNIT_MINUTE => $this->setMinute($value),
			self::UNIT_HOUR => $this->setHour($value),
			self::UNIT_WEEKDAY => $this->setWeekday($value),
			self::UNIT_DAY => $this->setDay($value),
			self::UNIT_MONTH => $this->setMonth($value),
			self::UNIT_QUARTER => $this->setQuarter($value),
			self::UNIT_YEAR => $this->setYear($value),
			default => throw new Exception_Key($unit, [
				'unit' => $unit, 'value' => $value,
			]),
		};
	}

	/**
	 * Add a unit to this Timestamp.
	 *
	 * @param int|float $n_units
	 *            Number of units to add (may be negative)
	 * @param string $unit
	 *            One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @return Timestamp
	 * @throws Exception_Key|Exception_Semantics
	 */
	public function addUnit(int|float $n_units = 1, string $unit = self::UNIT_SECOND): self {
		return match ($unit) {
			self::UNIT_MILLISECOND => $this->add(0, 0, 0, 0, 0, intval(round($n_units * self::MILLISECONDS_PER_SECONDS))),
			self::UNIT_SECOND => $this->add(0, 0, 0, 0, 0, $n_units),
			self::UNIT_MINUTE => $this->add(0, 0, 0, 0, $n_units),
			self::UNIT_HOUR => $this->add(0, 0, 0, $n_units),
			self::UNIT_WEEKDAY, self::UNIT_DAY => $this->add(0, 0, $n_units),
			self::UNIT_WEEK => $this->add(0, 0, $n_units * self::DAYS_PER_WEEK),
			self::UNIT_MONTH => $this->add(0, $n_units),
			self::UNIT_QUARTER => $this->add(0, $n_units * self::MONTHS_PER_QUARTER),
			self::UNIT_YEAR => $this->add($n_units),
			default => throw new Exception_Key('{method)({n_units}, {units}): Invalid unit', [
				'method' => __METHOD__, 'n_units' => $n_units, 'units' => $unit,
			]),
		};
	}

	/**
	 * Format YYYY${sep}MM${sep}DD
	 *
	 * @param string $sep
	 * @return string
	 */
	private function _ymd_format(string $sep = '-'): string {
		return $this->year() . $sep . StringTools::zeroPad($this->month()) . $sep . StringTools::zeroPad($this->day());
	}

	/**
	 * Format HH${sep}MM${sep}SS
	 *
	 * @param string $sep
	 * @return string
	 */
	private function _hms_format(): string {
		return implode(':', [
			StringTools::zeroPad($this->hour()), StringTools::zeroPad($this->minute()),
			StringTools::zeroPad($this->second()),
		]);
	}

	/**
	 * Convert to SQL format
	 *
	 * @return string
	 */
	public function sql(): string {
		return $this->_ymd_format() . ' ' . $this->_hms_format();
	}

	/**
	 * Get or set a iso8601 date format
	 *
	 * @return string
	 * @see http://en.wikipedia.org/wiki/ISO_8601
	 */
	public function iso8601(): string {
		return $this->_ymd_format('') . 'T' . $this->_hms_format();
	}

	/**
	 * @param string $set
	 * @return $this
	 * @throws Exception_Syntax
	 */
	public function setISO8601(string $set): self {
		$value = trim($set);
		// if (preg_match('/[0-9]{4}-([0-9]{2}-[0-9]{2}|W[0-9]{2}|[0-9]{3})(T[0-9]{2}(:?[0-9]{2}(:?[0-9]{2}))/',
		// $value, $matches)) {
		// TODO support iso8601 latest
		// }
		[$dd, $tt] = pair(strtoupper($value), 'T');
		if ($dd === '0000') {
			$this->setEmpty();
			return $this;
		}
		if ($dd === false || $tt === false) {
			throw new Exception_Syntax(map('Timestamp::iso8601({0}) - invalid date format', [$set]));
		}
		[$hh, $mm, $ss] = explode(':', $tt, 3);
		$this->ymdhms(intval(substr($dd, 0, 4)), intval(substr($dd, 4, 2)), intval(substr($dd, 6, 2)), intval($hh), intval($mm), intval($ss));
		return $this;
	}

	/**
	 * Create a new Timestamp with explicit values
	 *
	 * @param ?int $year
	 * @param ?int $month
	 * @param ?int $day
	 * @param ?int $hour
	 * @param ?int $minute
	 * @param ?int $second
	 * @param ?int $millisecond
	 * @return Timestamp
	 * @see Timestamp::factory_ymdhms
	 */
	public static function instance(int $year = null, int $month = null, int $day = null, int $hour = null, int $minute = null, int $second = null, int $millisecond = null): self {
		$dt = new Timestamp();
		$dt->ymd($year, $month, $day);
		$dt->hms($hour, $minute, $second);
		if ($millisecond !== null) {
			$dt->setMillisecond($millisecond);
		}
		return $dt;
	}

	/**
	 * Create a new Timestamp with explicit values
	 *
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return Timestamp
	 */
	public static function factory_ymdhms(int $year = null, int $month = null, int $day = null, int $hour = null, int $minute = null, int $second = null, int $millisecond = null) {
		$dt = new Timestamp();
		$dt->ymd($year, $month, $day);
		$dt->hms($hour, $minute, $second);
		if ($millisecond !== null) {
			$dt->setMillisecond($millisecond);
		}
		return $dt;
	}

	/**
	 * Use with usort or uasort of Timestamp[]
	 *
	 * @param Timestamp $a
	 * @param Timestamp $b
	 * @return integer
	 */
	public static function compare_callback(Timestamp $a, Timestamp $b) {
		$delta = $a->unixTimestamp() - $b->unixTimestamp();
		if ($delta < 0) {
			return -1;
		} elseif ($delta === 0) {
			return 0;
		}
		return 1;
	}
}
