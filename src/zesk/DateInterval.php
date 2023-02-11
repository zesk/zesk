<?php
declare(strict_types=1);
/**
 * Utility class for \DateInterval
 */
namespace zesk;

/**
 *
 */
class DateInterval extends \DateInterval {
	/**
	 * Quick way to copy around DateInterval representations using constructor
	 *
	 * @var string
	 * @see \DateInterval
	 */
	public const INTERVAL_SPEC_FORMAT = 'P%yY%mM%dDT%hH%iM%sS';

	/**
	 * Convert a \DateInterval to a zesk\DateInterval
	 *
	 * @param string $duration
	 * @return self
	 * @throws \Exception
	 */
	public static function factory(string $duration): self {
		return new self($duration);
	}

	/**
	 * Convert a \DateInterval to a zesk\DateInterval
	 *
	 * @return self
	 * @throws \Exception
	 */
	public static function extend(\DateInterval $interval): self {
		return new self($interval->format(self::INTERVAL_SPEC_FORMAT));
	}

	/**
	 * @return float
	 */
	public function toSeconds(): float {
		$secs = 0;
		$secs += $this->y * Temporal::SECONDS_PER_YEAR;
		$secs += $this->m * Temporal::SECONDS_PER_MONTH;
		$secs += $this->d * Temporal::SECONDS_PER_DAY;

		$secs += $this->h * Temporal::SECONDS_PER_HOUR;
		$secs += $this->i * Temporal::SECONDS_PER_MINUTE;
		$secs += $this->s;

		return $secs + $this->f;
	}

	/**
	 * Update this DateInterval from seconds given
	 *
	 * @param double $value
	 * @return DateInterval
	 */
	public function fromSeconds(float $value): self {
		static $units = [
			's' => Temporal::SECONDS_PER_MINUTE,
			'i' => Temporal::MINUTES_PER_HOUR,
			'h' => Temporal::HOURS_PER_DAY,
			'd' => Temporal::DAYS_PER_MONTH,
			'm' => Temporal::MONTHS_PER_YEAR,
			'y' => 10000, // Think we'll live to year 10,000? I think not.
		];
		if ($value < 0) {
			$value = -$value;
			$this->invert = 1;
		} else {
			$this->invert = 0;
		}
		$this->days = false;
		$this->f = $value - intval($value);
		$value = intval($value);
		foreach ($units as $k => $per) {
			if ($value === 0) {
				break;
			}
			$this->$k = $temp = $value % intval($per);
			$value = intval(($value - $temp) / $per);
		}
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->format(self::INTERVAL_SPEC_FORMAT);
	}
}
