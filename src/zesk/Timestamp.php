<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use OutOfBoundsException;
use Throwable;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Locale\Locale;

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

	/**
	 *
	 */
	public const FORMAT_JSON = '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {ZZZ}';

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
	 * Internal yearDay format - do not use
	 *
	 * @var string
	 */
	public const DATETIME_FORMAT_YEARDAY = 'z';

	/**
	 *
	 * @return DateTimeZone
	 */
	public static function utcTimeZone(): DateTimeZone {
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
	 * @param null|DateTimeInterface|int|Time|Date|Timestamp $value
	 * @param DateTimeZone|null $timezone
	 */
	public function __construct(null|DateTimeInterface|int|Time|Date|Timestamp $value = null, DateTimeZone $timezone = null) {
		$this->milliseconds = 0;
		if ($value instanceof DateTimeInterface) {
			$this->tz = $value->getTimezone();

			try {
				$this->datetime = new DateTime('now', $this->tz);
			} catch (Exception) {
			}
			$this->setUnixTimestamp($value->getTimestamp());
		} else {
			$this->tz = $timezone === null ? self::timezone_local() : $timezone;
			if ($value !== null) {
				try {
					$this->datetime = new DateTime('now', $this->tz);
				} catch (Exception) {
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
	 * @param int|Date|Time|Timestamp|null $value
	 * @param string|DateTimeZone|null $timezone
	 * @return static
	 */
	public static function factory(null|int|Date|Time|Timestamp $value = null, string|DateTimeZone $timezone =
	null): self {
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
		return self::factory(null, $timezone)->setNow();
	}

	/**
	 * Just prefer utc as an acronym.
	 * Returns UTC Timestamp set to current time.
	 *
	 * @return Timestamp
	 */
	public static function nowUTC(): self {
		return self::factory(null, self::utcTimeZone())->setNow();
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
		$this->setYMD($date->year(), $date->month(), $date->day());
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
		$this->setHMS($time->hour(), $time->minute(), $time->second());
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
	 * @return bool
	 */
	public function isEmpty(): bool {
		return $this->datetime === null;
	}

	/**
	 * Set this Timestamp to empty
	 *
	 * @return Timestamp
	 */
	public function setEmpty(): self {
		$this->datetime = null;
		return $this;
	}

	/**
	 * Set the Timestamp with a variety of formats (string use parse)
	 *
	 * @param null|int|Date|Time|Timestamp $value
	 * @return $this
	 * @see self::parse()
	 */
	public function set(null|int|Date|Time|Timestamp $value): self {
		if (empty($value)) {
			$this->setEmpty();
			return $this;
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
			try {
				$this->datetime = new DateTime('now', $this->tz);
			} catch (Exception) {
			}
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
	 * @return bool
	 * @throws ParseException
	 */
	public function parseLocaleString(string $value, string $locale_format = 'MDY;MD;MY;_'): bool {
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
							return $this->parseLocaleString(substr($value, 0, 2) . '/' . substr($value, 2, 2) . '/' . substr($value, 4));
						}

						throw new ParseException('Timestamp::parse_locale_string({value},{locale_format}): Unknown format', [
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
	 * @throws ParseException
	 */
	public function parse(string $value): self {
		// This fails on a cookie date sent by 64-bit systems
		// Set-Cookie: TrkCookieID=51830899; expires=Sat, 16-Aug-2064 04:11:10 GMT
		// DAY, DD-MMM-YYYY HH:MM:SS GMT
		$matches = null;
		$month_names = $this->_month_names_en();
		if (preg_match('/([0-9]{2})-([A-Z]{3})-([0-9]{4}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])/i', "$value", $matches)) {
			$mm = $month_names[strtolower($matches[2])] ?? 1;
			$this->setYMD(intval($matches[3]), $mm, intval($matches[1]));
			$this->setHMS(intval($matches[4]), intval($matches[5]), intval($matches[6]));
			return $this;
		}
		$parsed = strtotime($value, time());
		if ($parsed === false) {
			throw new ParseException('Timestamp::parse({value})', ['value' => $value]);
		}

		try {
			$datetime = new DateTime($value, $this->tz);
		} catch (Exception $e) {
			PHP::log($e->getMessage());
			$datetime = null;
		}
		$this->datetime = $datetime;
		return $this;
	}

	/**
	 * Get/Set year
	 *
	 * @return int number
	 */
	public function year(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_YEAR)) : -1;
	}

	/**
	 * Get/Set year
	 *
	 * @param int $set
	 * @return Timestamp number
	 */
	public function setYear(int $set): self {
		$this->_datetime()->setDate($set, $this->month(), $this->day());
		return $this;
	}

	/**
	 * Set a 1-based quarter (1,2,3,4)
	 *
	 * @return int
	 */
	public function quarter(): int {
		return $this->datetime ? intval(($this->month() - 1) * (4 / 12)) + 1 : -1;
	}

	/**
	 * Set a 1-based quarter (1,2,3,4)
	 *
	 * @param int $set
	 * @return Timestamp
	 * @throws Semantics
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
	public function setMonth(int $set): static {
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
	public function isToday(): bool {
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
	 * @throws Semantics
	 */
	public function setWeekdayPast(int $set): self {
		try {
			return $this->setWeekday($set)->addUnit(-7, self::UNIT_DAY);
		} catch (KeyNotFound) {
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
	 * Set weekday
	 *
	 * Weekday, when set, is always the NEXT possible weekday, including today.
	 *
	 * @param int $set
	 * @return Timestamp
	 * @throws Semantics
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
	 * Get yearday
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
	 * @throws Semantics
	 */
	public function setYearday(int $set): self {
		$yearday = $this->yearday();
		return $this->add(0, 0, $set - $yearday);
	}

	/**
	 * Get hour of day
	 *
	 * @return int
	 */
	public function hour(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_HOUR)) : -1;
	}

	/**
	 * Set hour of day
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
	 * Get second of the day
	 *
	 * @return int
	 */
	public function second(): int {
		return $this->datetime ? intval($this->datetime->format(self::DATETIME_FORMAT_SECOND)) : -1;
	}

	/**
	 * Set second of the day
	 *
	 * @param int $set
	 * @return Timestamp
	 */
	public function setSecond(int $set): self {
		$this->_datetime()->setTime($this->hour(), $this->minute(), $set);
		return $this;
	}

	/**
	 * Get millisecond
	 *
	 * @return integer
	 */
	public function millisecond(): int {
		return $this->datetime ? $this->milliseconds : 0;
	}

	/**
	 * Set millisecond
	 * @param int $set
	 * @return $this
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
		$midnight->setMidnight();
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
	public function setMidnight(): self {
		$this->_datetime()->setTime(0, 0);
		return $this;
	}

	public function setNoon(): self {
		$this->_datetime()->setTime(12, 0);
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
	public function setYMD(int $year = null, int $month = null, int $day = null): self {
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
	public function setHMS(int $hour = null, int $minute = null, int $second = null): self {
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
	 * @return self
	 */
	public function setYMDHMS(int $year = null, int $month = null, int $day = null, int $hour = null, int $minute = null, int $second = null): self {
		return $this->setYMD($year, $month, $day)->setHMS($hour, $minute, $second);
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
	 * Format a Timestamp
	 *
	 * @param string $format
	 * @param array $options
	 * @return string
	 * @throws ParseException
	 */
	public function format(string $format = '', array $options = []): string {
		if ($format === '') {
			$format = self::DEFAULT_FORMAT_STRING;
		}
		return ArrayTools::map($format, $this->formatting($options));
	}

	/**
	 * Formatting a timestamp string
	 *
	 * @param Locale|null $locale
	 * @param array $options
	 *            'locale' => string. Locale to use, if any
	 *            'unit_minimum' => string. Minimum time unit to display
	 *            'zero_string' => string. What to display when closer to the unit_minimum to the
	 *            time
	 *            'nohook' => bool. Do not invoke the formatting hook
	 * @return array
	 * @throws ParseException
	 * @see Locale::nowString
	 * @hook Timestamp::formatting
	 */
	public function formatting(array $options = []): array {
		$locale = $options['locale'] ?? null;
		$ts = $this->unixTimestamp();
		$formatting = $this->date()->formatting($options) + $this->time()->formatting($options);

		$formatting += ['seconds' => $ts, 'unixTimestamp()' => $ts, 'Z' => '-', 'ZZZ' => '---', ];

		if ($locale instanceof Locale) {
			$config_timestamp = $locale->application->configuration->path([__CLASS__, 'formatting']);
			$unit_minimum = $options['unit_minimum'] ?? $config_timestamp->getString('unit_minumum');
			$zero_string = $options['zero_string'] ?? $config_timestamp->getString('zero_string');
			// Support $unit_minimum and $zero_string strings which include formatting
			$unit_minimum = ArrayTools::map($unit_minimum, $formatting);
			$zero_string = ArrayTools::map($zero_string, $formatting);

			try {
				$formatting['delta'] = $locale->nowString($this, strval($unit_minimum), strval($zero_string));
			} catch (ParseException) {
				$formatting['delta'] = '';
			}
		}
		if ($this->datetime) {
			// TODO This doesn't actually honor the current locale
			$formatting = ['Z' => $this->datetime->format('e'), 'ZZZ' => $this->datetime->format('T'), ] + $formatting;
		}
		if ($locale instanceof Locale && !($options['nohook'] ?? false)) {
			$formatting = $locale->application->hooks->callArguments(__CLASS__ . '::formatting', [
				$this, $formatting, $options,
			], $formatting);
		}
		return $formatting;
	}

	/**
	 * Are these two timestamps identical?
	 *
	 * @param Timestamp $timestamp
	 * @return bool
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
	 * @param bool $equal
	 *            Return true if they are equal
	 * @return bool
	 */
	public function before(Timestamp $model, bool $equal = false): bool {
		$result = $this->compare($model);
		return ($equal) ? ($result <= 0) : ($result < 0);
	}

	/**
	 * Shortcut to test if time is before current time
	 *
	 * @param bool|string $equal
	 *            Return true if time MATCHES current time (seconds)
	 * @return bool
	 */
	public function beforeNow(bool $equal = false): bool {
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
	 * @param bool $equal
	 *            Return true if they are equal
	 * @return bool
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
	 * @return bool
	 */
	public function isPast(): bool {
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
	 * @throws Semantics
	 */
	public function add(int $years = 0, int $months = 0, int $days = 0, int $hours = 0, int $minutes = 0, int $seconds = 0, int $milliseconds = 0): self {
		if ($this->datetime === null) {
			throw new Semantics('Adding to a empty Timestamp');
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
	 * @param bool $time
	 * @return Timestamp
	 */
	private function _addUnit(int $number, string $code, bool $time = false): self {
		try {
			$interval = new DateInterval('P' . ($time ? 'T' : '') . abs($number) . $code);
			if ($number < 0) {
				$interval->invert = true;
			}
			return $this->addInterval($interval);
		} catch (Throwable) {
			// Should never if parameters above are ok
		}
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
	 * @return int
	 * @throws ParameterException
	 */
	public function unit(string $unit): int {
		return match ($unit) {
			self::UNIT_MILLISECOND => $this->millisecond(),
			self::UNIT_SECOND => $this->second(),
			self::UNIT_MINUTE => $this->minute(),
			self::UNIT_HOUR => $this->hour(),
			self::UNIT_WEEKDAY => $this->weekday(),
			self::UNIT_DAY => $this->day(),
			self::UNIT_MONTH => $this->month(),
			self::UNIT_QUARTER => $this->quarter(),
			self::UNIT_YEAR => $this->year(),
			default => throw new ParameterException('Timestamp::unit({unit}): Bad unit', ['unit' => $unit]),
		};
	}

	/**
	 * Set a unit.
	 *
	 * @param string $unit
	 *            One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @param int $value
	 *            Value to set
	 * @return Timestamp integer
	 * @throws KeyNotFound|OutOfBoundsException
	 * @throws Semantics
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
			default => throw new KeyNotFound($unit, [
				'unit' => $unit, 'value' => $value,
			]),
		};
	}

	/**
	 * Add a unit to this Timestamp.
	 *
	 * @param int|float $n_units
	 *            Number of units to add (negative is ok)
	 * @param string $unit
	 *            One of millisecond, second, minute, hour, weekday, day, month, quarter, year
	 * @return Timestamp
	 * @throws KeyNotFound|Semantics
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
			default => throw new KeyNotFound('{method)({n_units}, {units}): Invalid unit', [
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
	 * Return timestamp formatted in iso8601 date format
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
	 * @throws SyntaxException
	 */
	public function setISO8601(string $set): self {
		$value = trim($set);
		// if (preg_match('/[0-9]{4}-([0-9]{2}-[0-9]{2}|W[0-9]{2}|[0-9]{3})(T[0-9]{2}(:?[0-9]{2}(:?[0-9]{2}))/',
		// $value, $matches)) {
		// TODO support iso8601 latest
		// }
		[$dd, $tt] = StringTools::pair(strtoupper($value), 'T');
		if ($dd === '0000') {
			$this->setEmpty();
			return $this;
		}
		if ($dd === false || $tt === false) {
			throw new SyntaxException('Timestamp::iso8601({set}) - invalid date format', ['set' => $set]);
		}
		[$hh, $mm, $ss] = explode(':', $tt, 3);
		$this->setYMDHMS(intval(substr($dd, 0, 4)), intval(substr($dd, 4, 2)), intval(substr($dd, 6, 2)), intval($hh), intval($mm), intval($ss));
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
	 * @see Timestamp::instance
	 */
	public static function instance(int $year = null, int $month = null, int $day = null, int $hour = null, int $minute = null, int $second = null, int $millisecond = null): self {
		$dt = new Timestamp();
		$dt->setYMD($year, $month, $day);
		$dt->setHMS($hour, $minute, $second);
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
	public static function compare_callback(Timestamp $a, Timestamp $b): int {
		$delta = $a->unixTimestamp() - $b->unixTimestamp();
		if ($delta < 0) {
			return -1;
		} elseif ($delta === 0) {
			return 0;
		}
		return 1;
	}
}
