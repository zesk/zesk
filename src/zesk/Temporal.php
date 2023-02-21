<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright 2022 Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\KeyNotFound;
use zesk\Interface\Formatting;
use zesk\Locale\Locale;

/**
 *
 * @author kent
 *
 */
abstract class Temporal implements Formatting {
	/**
	 *
	 * @var string
	 */
	public const UNIT_YEAR = 'year';

	/*
	 * @var string
	 */
	public const UNIT_QUARTER = 'quarter';

	/*
	 * @var string
	 */
	public const UNIT_MONTH = 'month';

	/*
	 * @var string
	 */
	public const UNIT_WEEKDAY = 'weekday';

	/*
	 * @var string
	 */
	public const UNIT_WEEK = 'week';

	/*
	 * @var string
	 */
	public const UNIT_DAY = 'day';

	/*
	 * @var string
	 */
	public const UNIT_HOUR = 'hour';

	/*
	 * @var string
	 */
	public const UNIT_MINUTE = 'minute';

	/*
	 * @var string
	 */
	public const UNIT_SECOND = 'second';

	/*
	 * @var string
	 */
	public const UNIT_MILLISECOND = 'millisecond';

	/**
	 * Duh.
	 *
	 * @var integer
	 */
	public const MILLISECONDS_PER_SECONDS = 1000;

	/**
	 * @var integer
	 */
	public const SECONDS_PER_MINUTE = 60;

	/**
	 * @var integer
	 */
	public const MINUTES_PER_HOUR = 60;

	/**
	 * @var integer
	 */
	public const HOURS_PER_DAY = 24;

	/**
	 * @var integer
	 */
	public const DAYS_PER_YEAR = 365.25; // Leap

	/**
	 * @var integer
	 */
	public const MONTHS_PER_YEAR = 12;

	/**
	 * @var integer
	 */
	public const MONTHS_PER_QUARTER = 3;

	/**
	 * @var integer
	 */
	public const DAYS_PER_WEEK = 7;

	/**
	 *
	 * @var integer
	 */
	public const DAYS_PER_QUARTER = self::DAYS_PER_YEAR / 4;

	/**
	 * @var double
	 */
	public const DAYS_PER_MONTH = self::DAYS_PER_YEAR / self::MONTHS_PER_YEAR;

	/**
	 * @var integer
	 */
	public const SECONDS_PER_DAY = self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR * self::HOURS_PER_DAY;

	/**
	 * @var integer
	 */
	public const SECONDS_PER_WEEK = self::SECONDS_PER_DAY * self::DAYS_PER_WEEK;

	/**
	 *
	 * @var double
	 */
	public const SECONDS_PER_YEAR = self::SECONDS_PER_DAY * self::DAYS_PER_YEAR;

	/**
	 *
	 * @todo PHP7 use calculation
	 * @var double
	 */
	public const SECONDS_PER_QUARTER = self::SECONDS_PER_DAY * self::DAYS_PER_QUARTER;

	/**
	 *
	 * @todo PHP7 use calculation
	 * @var double
	 */
	public const SECONDS_PER_MONTH = self::SECONDS_PER_YEAR / self::MONTHS_PER_YEAR;

	/**
	 *
	 * @todo PHP7 use calculation
	 * @var double
	 */
	public const SECONDS_PER_HOUR = self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR;

	/**
	 * Translate units into seconds
	 *
	 * @var array
	 */
	public static array $UNITS_TRANSLATION_TABLE = [
		self::UNIT_YEAR => self::SECONDS_PER_YEAR,
		self::UNIT_QUARTER => self::SECONDS_PER_QUARTER,
		self::UNIT_MONTH => self::SECONDS_PER_MONTH, // 365*86400/12 (average 30.42 days)
		self::UNIT_WEEK => self::SECONDS_PER_WEEK, // 60*60*24*7
		self::UNIT_DAY => self::SECONDS_PER_DAY, // 60*60*24
		self::UNIT_HOUR => self::SECONDS_PER_HOUR, // 60*60
		self::UNIT_MINUTE => self::SECONDS_PER_MINUTE, // 60
		self::UNIT_SECOND => 1, // 1:1
		self::UNIT_MILLISECOND => 0.001, // 1:1000
	];

	/**
	 * Convert to SQL format
	 *
	 * @return string
	 */
	abstract public function sql(): string;

	/**
	 * Format
	 * @param string $format
	 * @param array $options
	 * @return string
	 */
	abstract public function format(string $format = '', array $options = []): string;

	/**
	 * Fetch formatting for this object
	 *
	 * @param array $options
	 * @return array
	 */
	abstract public function formatting(array $options = []): array;

	/**
	 * Return an array of unit => seconds (integer)
	 *
	 * @return array
	 */
	public static function unitsTranslationTable(): array {
		return self::$UNITS_TRANSLATION_TABLE;
	}

	/**
	 * Return seconds for a unit
	 *
	 * @param string $unit
	 * @return int
	 * @throws KeyNotFound
	 */
	public static function unitToSeconds(string $unit): int {
		$result = self::unitsTranslationTable();
		if (!array_key_exists($unit, $result)) {
			throw new KeyNotFound($unit);
		}
		return $result[$unit];
	}

	/**
	 * Convert from seconds to a greater unit
	 *
	 * @param int|float $seconds
	 * @param string $unit
	 * @return float
	 * @throws KeyNotFound
	 */
	public static function convertUnits(int|float $seconds, string $unit = self::UNIT_SECOND): float {
		return floatval($seconds / self::unitToSeconds($unit));
	}

	/**
	 * Convert seconds into a particular unit
	 *
	 * @param int $seconds
	 *            Number of seconds to convert to a unit.
	 * @param string $stop_unit
	 *            Unit to stop comparing for. If you only want to know how many months away
	 *            something is, specify a higher value.
	 * @param ?float $fraction
	 *            Returns $seconds divided by total units, can be used to specify 2.435 years, for
	 *            example.
	 * @return string The units closest to the number of seconds
	 */
	public static function secondsToUnit(int $seconds, string $stop_unit = self::UNIT_SECOND, float &$fraction = null): string {
		$translation = self::$UNITS_TRANSLATION_TABLE;
		$unit = '';
		foreach ($translation as $unit => $unit_seconds) {
			if (($seconds >= $unit_seconds) || ($stop_unit === $unit)) {
				$fraction = floatval($seconds / $unit_seconds);
				return $unit;
			}
		}
		$fraction = $seconds;
		return $unit;
	}
}
