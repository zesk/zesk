<?php
declare(strict_types=1);
/**
 *
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class TimeSpan extends Temporal {
	/**
	 *
	 * @var integer
	 */
	protected int $duration = 0;

	/**
	 * @var bool
	 */
	protected bool $invert = false;

	/**
	 * @throws Exception_Syntax
	 * @param int|float|string $seconds
	 */
	public function __construct(int|float|string $seconds = 0) {
		$this->setSeconds($seconds);
	}

	/**
	 * @param int|float|string $seconds
	 * @return self
	 * @throws Exception_Syntax
	 */
	public static function factory(int|float|string $seconds = 0): self {
		return new self($seconds);
	}

	/**
	 * Either pass in a number of seconds, or a string representing the time span, like "3 seconds"
	 * or "3 days" or "2019/02/20" and it will compute the relative time between now and that duration.
	 * Returns negative numbers.
	 *
	 * @param string|mixed $mixed
	 * @throws Exception_Syntax if it can't conver mixed into a time
	 * @return int
	 */
	public static function parse(int|float|string $mixed): int {
		if (is_numeric($mixed)) {
			return intval($mixed);
		}
		if (is_string($mixed)) {
			$delta = strtotime($mixed);
			if ($delta !== false) {
				$result = $delta - time();
				if ($result > 0) {
					return $result;
				}
			}
		}

		throw new Exception_Syntax('{method} can not parse {mixed}', [
			'method' => __METHOD__,
			'mixed' => $mixed,
		]);
	}

	/**
	 * Add seconds to time span
	 *
	 * @param int $seconds
	 *
	 * @return $this
	 */
	public function add(int $seconds): self {
		$this->duration = $this->duration + $seconds;
		return $this;
	}

	/**
	 * Getter for the duration in seconds
	 *
	 * @return int
	 */
	public function seconds(): int {
		return $this->duration;
	}

	/**
	 * Setter for the duration in seconds
	 *
	 * @param int|float|string $set
	 * @return $this
	 * @throws Exception_Syntax
	 */
	public function setSeconds(int|float|string $set): self {
		$this->duration = $this->parse($set);
		if ($this->duration < 0) {
			$this->duration = -$this->duration;
			$this->invert = true;
		} else {
			$this->invert = false;
		}
		return $this;
	}

	/**
	 * Convert to SQL format (an integer as string)
	 *
	 * @return string
	 */
	public function sql(): string {
		return strval($this->duration);
	}

	/**
	 * Format time span
	 *
	 * @param Locale|null $locale
	 * @param string $format_string
	 * @param array $options
	 * @return string
	 */
	public function format(Locale $locale = null, string $format_string = '', array $options = []): string {
		if (!$format_string) {
			$format_string = '{seconds}';
		}
		return map($format_string, $this->formatting($locale, $options));
	}

	/**
	 * Fetch formatting for this object
	 *
	 * @param Locale|null $locale
	 * @param array $options
	 * @return array
	 */
	public function formatting(Locale $locale = null, array $options = []): array {
		$seconds = $this->seconds();
		$ss = $seconds % 60;
		$minutes = floor($seconds / 60);
		$mm = $minutes % 60;
		$hours = floor($seconds / 3600);
		$hh = $hours % 24;
		$days = intval(floor($seconds / 86400));

		return [
			'negative' => $this->invert ? '-' : '',
			'seconds' => $seconds,
			'ss' => StringTools::zeroPad($ss, 2),
			'minutes' => $minutes,
			'mm' => StringTools::zeroPad($mm, 2),
			'hours' => $hours,
			'hh' => StringTools::zeroPad($hh, 2),
			'days' => $days,
			'dd' => StringTools::zeroPad($days, 2),
			'ddd' => StringTools::zeroPad($days % 365, 3),
		];
	}
}
