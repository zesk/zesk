<?php

/**
 *
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
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
	protected $duration = null;

	/**
	 *
	 * @param mixed $seconds
	 */
	public function __construct($seconds = null) {
		$this->duration = $this->parse($seconds);
	}

	/**
	 *
	 * @return boolean
	 */
	public function valid() {
		return is_numeric($this->duration);
	}

	/**
	 * Either pass in a number of seconds, or a string representing the time span, like "3 seconds"
	 * or "3 days" or "2019/02/20" and it will compute the relative time between now and that duration.
	 *
	 * @param string|mixed $mixed
	 * @throws Exception_Syntax if it can't conver mixed into a time
	 * @return number|NULL
	 */
	public function parse($mixed) {
		if (is_numeric($mixed)) {
			return $mixed;
		}
		if (is_string($mixed)) {
			$delta = strtotime($mixed);
			if ($delta !== false) {
				$result = $delta - time();
				if ($result > 0) {
					return $result;
				}
			}

			throw new Exception_Syntax("{method} can not parse {mixed}", array(
				"method" => __METHOD__,
				"mixed" => $mixed,
			));
		}
		return null;
	}

	/**
	 * Add seconds to time span
	 *
	 * @param integer $seconds
	 *
	 * @return $this
	 */
	public function add($seconds) {
		$this->duration = $this->duration + $seconds;
		return $this;
	}

	/**
	 * Getter/setter for the duration in seconds
	 *
	 * @param string|integer|null $set
	 * @return integer|$this
	 */
	public function seconds($set = null) {
		if ($set === null) {
			return $this->duration;
		}
		$this->duration = $this->parse($set);
		return $this;
	}

	/**
	 * Convert to SQL format (an integer as string)
	 *
	 * @return string
	 */
	public function sql() {
		return $this->valid() ? strval($this->duration) : null;
	}

	/**
	 * Format time span
	 *
	 * @param string $format_string
	 * @param array $options
	 * @return string
	 */
	public function format(Locale $locale = null, $format_string = null, array $options = array()) {
		if (!$format_string) {
			$format_string = "{seconds}";
		}
		return map($format_string, $this->formatting($locale, $options));
	}

	/**
	 * Fetch formatting for this object
	 *
	 * @param array $options
	 * @return array
	 */
	public function formatting(Locale $locale = null, array $options = array()) {
		$seconds = $this->seconds();
		if ($seconds !== null) {
			$ss = intval($seconds) % 60;
			$minutes = floor($seconds / 60);
			$mm = $minutes % 60;
			$hours = floor($seconds / 3600);
			$hh = $hours % 24;
			$dd = $days = floor($seconds / 86400);

			return array(
				"seconds" => $seconds,
				"ss" => StringTools::zero_pad($ss, 2),
				"minutes" => $minutes,
				"mm" => StringTools::zero_pad($mm, 2),
				"hours" => $hours,
				"hh" => StringTools::zero_pad($hh, 2),
				"days" => $days,
				"ddd" => StringTools::zero_pad($days % 365, 3),
			);
		}
		return array(
			"seconds" => '-',
			"ss" => "-",
			"minutes" => '-',
			"mm" => '-',
			"hours" => '-',
			"hh" => '-',
			"days" => '-',
			"ddd" => '-',
		);
	}
}
