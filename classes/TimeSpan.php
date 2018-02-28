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
	function __construct($seconds = null) {
		$this->duration = $this->parse($seconds);
	}

	/**
	 *
	 * @return boolean
	 */
	function valid() {
		return is_numeric($this->duration);
	}
	/**
	 *
	 * @param string|mixed $mixed
	 * @throws Exception_Syntax
	 * @return unknown|number|NULL
	 */
	function parse($mixed) {
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
				"mixed" => $mixed
			));
		}
		return null;
	}

	/**
	 *
	 * @param string|integer|null $set
	 * @return number|\zesk\TimeSpan
	 */
	function seconds($set = null) {
		if ($set === null) {
			return $this->duration;
		}
		$this->duration = $this->parse($set);
		return $this;
	}

	/**
	 * Convert to SQL format
	 *
	 * @return string
	 */
	function sql() {
		return $this->valid() ? strval($this->duration) : null;
	}

	/**
	 * Format time span
	 *
	 * @param string $format_string
	 * @param array $options
	 * @return string
	 */
	function format(Locale $locale = null, $format_string = null, array $options = array()) {
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
	function formatting(Locale $locale = null, array $options = array()) {
		$seconds = $this->seconds();
		if ($seconds !== null) {
			return array(
				"seconds" => $seconds,
				"minutes" => round($seconds / 60),
				"hours" => round($seconds / 3600),
				"days" => round($seconds / 86400)
			);
		}
		return array(
			"seconds" => '-',
			"minutes" => '-',
			"hours" => '-',
			"days" => '-'
		);
	}
}