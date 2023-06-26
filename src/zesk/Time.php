<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

/**
 * Time of day
 *
 * @author kent
 * @package zesk
 * @subpackage model
 */

namespace zesk;

use OutOfBoundsException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Locale\Locale;

class Time extends Temporal {
	/**
	 *
	 * @var string
	 */
	public const DEFAULT_FORMAT_STRING = '{hh}:{mm}:{ss}';

	/**
	 * Set up upon load
	 *
	 * @var string
	 */
	private static string $default_format_string = self::DEFAULT_FORMAT_STRING;

	/**
	 * Maximum 0-based hour is 23
	 *
	 * @var integer
	 */
	public const hour_max = 23;

	/**
	 * Maximum 0-based second is 59
	 *
	 * @var integer
	 */
	public const second_max = 59;

	/**
	 * Maximum 0-indexed minute is 59
	 *
	 * @var integer
	 */
	public const minute_max = 59;

	/**
	 * Maximum value for seconds from midnight in a day
	 *
	 * @var integer
	 */
	public const seconds_max = 86399;

	/**
	 * 60 seconds in a minute
	 *
	 * @var integer
	 */
	public const seconds_per_minute = 60;

	/**
	 * 3,600 seconds an hour
	 *
	 * @var integer
	 */
	public const seconds_per_hour = 3600;

	/**
	 * 86,400 seconds in a day
	 *
	 * @var integer
	 */
	public const seconds_per_day = 86400;

	/**
	 * Integer value of seconds from midnight.
	 *
	 * Valid value range 0 to self::seconds_max
	 *
	 * If null, represents no value assigned, yet.
	 *
	 * @see self::seconds_max
	 * @var integer
	 */
	protected int $seconds = 0;

	/**
	 * Millisecond offset (0-999)
	 *
	 * @var integer
	 */
	protected int $milliseconds = 0;

	/**
	 * Create a new Time object by calling a static method
	 *
	 * @param int|float $hour
	 * @param int|float $minute
	 * @param int|float $second
	 * @return self
	 */
	public static function instance(int|float $hour = 0, int|float $minute = 0, int|float $second = 0): self {
		$tt = new Time();
		$tt->setHMS($hour, $minute, $second);
		return $tt;
	}

	/**
	 * Construct a new Time object
	 *
	 * @param null|int|Time|Timestamp $value
	 * @see Time::set
	 */
	public function __construct(null|int|Time|Timestamp $value = null) {
		$this->set($value);
	}

	/**
	 * Create a Time object
	 *
	 * @param mixed $value
	 * @return self
	 */
	public static function factory(null|int|Time|Timestamp $value = null): self {
		return new self($value);
	}

	/**
	 * Create exact replica of this object
	 *
	 * @return self
	 */
	public function duplicate(): self {
		return clone $this;
	}

	/**
	 * Return a new Time object representing current time of day
	 *
	 * @return self
	 */
	public static function now(): self {
		return self::factory()->setNow();
	}

	/**
	 * Set the time object
	 *
	 *
	 * @param null|int|Time|Timestamp $value Values of varying types
	 * @return self
	 */
	public function set(null|int|Time|Timestamp $value): self {
		if (is_int($value)) {
			return $this->setUNIXTimestamp($value);
		} elseif ($value instanceof Time) {
			$this->setSeconds($value->seconds());
			$this->setMillisecond($value->millisecond());
			return $this;
		} elseif ($value instanceof Timestamp) {
			$this->setUNIXTimestamp($value->unixTimestamp())->setMillisecond($value->millisecond());
			return $this;
		}
		$this->setEmpty();
		return $this;
	}

	/**
	 * Is this object empty?
	 *
	 * @return boolean
	 */
	public function isEmpty(): bool {
		return $this->seconds === -1;
	}

	/**
	 * Set this object as empty
	 *
	 * @return self
	 */
	public function setEmpty(): self {
		$this->seconds = -1;
		return $this;
	}

	/**
	 * Set the time to the current time of day
	 *
	 * @return self
	 */
	public function setNow(): self {
		$floatNow = microtime(true);
		$time = intval($floatNow);
		$milliseconds = intval(($floatNow - $time) * 1000.0);
		return $this->setUNIXTimestamp(time())->setMillisecond($milliseconds);
	}

	/**
	 * Set the time of day to midnight
	 *
	 * @return self
	 */
	public function setMidnight(): self {
		$this->seconds = 0;
		return $this;
	}

	/**
	 * Set the time of day to noon
	 *
	 * @return self
	 */
	public function setNoon(): self {
		$this->seconds = 0;
		return $this->setHour(12);
	}

	/**
	 * Set or get the unix timestamp.
	 *
	 * @return int
	 */
	public function unixTimestamp(): int {
		return $this->seconds;
	}

	/**
	 * Set or get the unix timestamp.
	 *
	 * @param int $set
	 * @return $this
	 * @throws OutOfBoundsException
	 */
	public function setUNIXTimestamp(int $set): self {
		[$hours, $minutes, $seconds] = explode(' ', gmdate('G n s', $set)); // doesn't support UTC
		$this->setHMS(intval($hours), intval($minutes), intval($seconds));
		return $this;
	}

	/**
	 * Set the hour, minute, and second of the day explicitly
	 *
	 * @param int $hh
	 * @param int $mm
	 * @param int $ss
	 * @return self
	 * @throws OutOfBoundsException
	 */
	public function setHMS(int $hh = 0, int $mm = 0, int $ss = 0): self {
		if (($hh < 0) || ($hh > self::hour_max) || ($mm < 0) || ($mm > self::minute_max) || ($ss < 0) || ($ss > self::second_max)) {
			throw new OutOfBoundsException("Time::hms($hh,$mm,$ss)");
		}
		$this->seconds = ($hh * self::seconds_per_hour) + ($mm * self::seconds_per_minute) + $ss;
		return $this;
	}

	/**
	 *
	 * @return string
	 * @todo should this honor locale? Or just be generic, programmer-only version
	 */
	public function __toString() {
		if ($this->isEmpty()) {
			return '';
		}
		return $this->format('{hh}:{mm}:{ss}');
	}

	/**
	 * Parse a time and set this object
	 *
	 * @param string $value
	 * @return self
	 * @throws OutOfBoundsException
	 * @throws ParseException
	 */
	public function parse(string $value): self {
		foreach ([
			'/([0-9]{1,2}):([0-9]{2}):([0-9]{2})/' => [
				null,
				'setHour',
				'setMinute',
				'setSecond',
			],
			'/([0-9]{1,2}):([0-9]{2})/' => [
				null,
				'setHour',
				'setMinute',
			],
		] as $pattern => $assign) {
			if (preg_match($pattern, $value, $matches)) {
				$this->setHMS();
				foreach ($assign as $index => $method) {
					if ($method) {
						$this->$method(intval($matches[$index]));
					}
				}
				return $this;
			}
		}
		$tz = date_default_timezone_get();
		date_default_timezone_set('UTC');
		$ts = strtotime($value, $this->unixTimestamp());
		date_default_timezone_set($tz);
		if ($ts === false || $ts < 0) {
			throw new ParseException('Time::parse({value}): Can\'t parse', ['value' => $value]);
		}
		return $this->setUNIXTimestamp($ts);
	}

	/**
	 * Get/set the hour of the day
	 *
	 * @return int
	 */
	public function hour(): int {
		return intval($this->seconds / self::seconds_per_hour);
	}

	/**
	 * Set the hour of the day
	 *
	 * @param int $set
	 * @return self
	 * @throws OutOfBoundsException
	 */
	public function setHour(int $set): self {
		return $this->setHMS($set, $this->minute(), $this->second());
	}

	/**
	 * Get/set the minute of the day
	 *
	 * @return int
	 */
	public function minute(): int {
		return intval($this->seconds / self::seconds_per_minute) % self::seconds_per_minute;
	}

	/**
	 * Set the minute of the day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setMinute(int $set): self {
		return $this->setHMS($this->hour(), $set, $this->second());
	}

	/**
	 * Get/set the second of the day
	 *
	 * @return int
	 */
	public function second(): int {
		return $this->seconds % self::seconds_per_minute;
	}

	/**
	 * Set the second of the day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setSecond(int $set): self {
		return $this->setHMS($this->hour(), $this->minute(), $set);
	}

	/**
	 * Get the second of the day (0 - 86399)
	 *
	 * @return int
	 */
	public function seconds(): int {
		return $this->seconds;
	}

	/**
	 * Set the second of the day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setSeconds(int $set): self {
		$this->seconds = $set;
		return $this;
	}

	/**
	 * Get the milliseconds of the second (0-999)
	 *
	 * @return int
	 */
	public function millisecond(): int {
		return $this->milliseconds;
	}

	/**
	 * Set the millisecond of the day
	 *
	 * @param int $set
	 * @return self
	 */
	public function setMillisecond(int $set): self {
		$this->milliseconds = $set % 1000;
		return $this;
	}

	/**
	 * Get/set the 12-hour of the day
	 *
	 * @return int
	 */
	public function hour12(): int {
		$hour = intval($this->seconds / self::seconds_per_hour);
		$hour = $hour % 12;
		if ($hour === 0) {
			$hour = 12;
		}
		return $hour;
	}

	/**
	 * Returns whether it's am or pm
	 *
	 * @return string
	 */
	public function ampm(): string {
		$hour = $this->hour();
		return ($hour < 12) ? 'am' : 'pm';
	}

	/**
	 * Get number of seconds since midnight
	 *
	 * @return integer
	 */
	public function daySeconds(): int {
		return $this->seconds;
	}

	/**
	 * Compare one time with another
	 *
	 * $this->compare($value) < 0 analogous to $this < $value
	 * $this->compare($value) > 0 analogous to $this > $value
	 *
	 * @param Time $value
	 * @return integer
	 */
	public function compare(Time $value): int {
		if ($this->isEmpty()) {
			if (!$value->isEmpty()) {
				return -1;
			} else {
				return 0;
			}
		} elseif ($value->isEmpty()) {
			return 1;
		}
		return $this->seconds - $value->seconds;
	}

	/**
	 * Subtract one time from another
	 *
	 * @param Time $value
	 * @return int
	 */
	public function subtract(Time $value): int {
		return $this->seconds - $value->seconds;
	}

	/**
	 * Add hours, minutes, seconds to a time
	 *
	 * @param int|float $hh
	 * @param int|float $mm
	 * @param int|float $ss
	 * @param ?int $remain Returned remainder of addition
	 * @return self
	 */
	public function add(int|float $hh = 0, int|float $mm = 0, int|float $ss = 0, int &$remain = null): self {
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
	 * @param array $options
	 * @return array
	 * @see Time::format
	 */
	public function formatting(array $options = []): array {
		$x = [];
		$x['h'] = $this->hour();
		$x['12h'] = $this->hour12();
		$x['m'] = $this->minute();
		$x['s'] = $this->second();
		foreach ($x as $k => $v) {
			$x[$k . substr($k, -1)] = StringTools::zeroPad($v);
		}
		$x['daySeconds'] = $this->seconds;
		$ampm = $this->ampm();
		$x['_ampm'] = $ampm;
		$locale = $options['locale'] ?? null;
		if ($locale instanceof Locale) {
			$x['ampm'] = $locale->__("Time:=$ampm");
			$ampm = strtoupper($ampm);
			$x['AMPM'] = $locale->__("Time:=$ampm");
		}
		return $x;
	}

	/**
	 * Format a time string
	 *
	 * @param string $format
	 * @param array $options
	 * @return string
	 */
	public function format(string $format = '', array $options = []): string {
		if ($format === '') {
			$format = self::$default_format_string;
		}
		$x = $this->formatting($options);
		return ArrayTools::map($format, $x);
	}

	/**
	 * Format HH${sep}MM${sep}SS
	 *
	 * @return string
	 */
	private function _hms_format(): string {
		return StringTools::zeroPad($this->hour()) . ':' . StringTools::zeroPad($this->minute()) . ':' . StringTools::zeroPad($this->second());
	}

	/**
	 * Format for SQL
	 *
	 * @return string
	 */
	public function sql(): string {
		return $this->_hms_format();
	}

	/**
	 * Add a unit to a time
	 *
	 * @param int $n_units
	 *            Number to add
	 * @param string $units
	 *            Unit to add: "millisecond", "second", "minute", "hour"
	 * @return self
	 * @throws ParameterException
	 */
	public function addUnit(int $n_units = 1, string $units = self::UNIT_SECOND): self {
		return match ($units) {
			self::UNIT_MILLISECOND => $this->add(0, 0, round($n_units / 1000)),
			self::UNIT_SECOND => $this->add(0, 0, $n_units),
			self::UNIT_MINUTE => $this->add(0, $n_units),
			self::UNIT_HOUR => $this->add($n_units),
			default => throw new ParameterException('{method)({n_units}, {units}): Invalid unit', [
				'method' => __METHOD__, 'n_units' => $n_units, 'units' => $units,
			]),
		};
	}

	/**
	 *
	 * @param Time $a
	 * @param Time $b
	 * @return number
	 */
	public static function sort_callback(Time $a, Time $b): int {
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
