<?php

namespace zesk;

abstract class Temporal {
	
	/**
	 * Convert to SQL format
	 * 
	 * @return string
	 */
	abstract function sql();
	
	/**
	 * Format
	 * @param string $format_string
	 * @param array $options
	 * @return string
	 */
	abstract function format($format_string = null, array $options = array());
	
	/**
	 * Fetch formatting for this object
	 * 
	 * @param array $options
	 * @return array
	 */
	abstract function formatting(array $options = array());
	
	/**
	 * Return an array of unit => seconds (integer)
	 *
	 * @return array
	 */
	public static function units_translation_table($unit = null) {
		$result = array(
			"year" => 31536000, // 365*86400 days
			"month" => 2628000, // 365*86400/12 (average 30.42 days)
			"week" => 604800, // 60*60*24*7
			"day" => 86400, // 60*60*24
			"hour" => 3600, // 60*60
			"minute" => 60, // 60
			"second" => 1
		); // 1
		if ($unit !== null) {
			return avalue($result, $unit, null);
		}
		return $result;
	}
	
	/**
	 * Convert from seconds to a greater unit
	 * 
	 * @param integer $seconds
	 * @param string $unit
	 * @throws Exception_Parameter
	 * @return double
	 */
	public static function convert_units($seconds, $unit = "second") {
		$seconds_in_unit = self::units_translation_table($unit);
		if ($seconds_in_unit === null) {
			throw new Exception_Parameter("Invalid unit name passed to {method}: {unit}", array(
				"method" => __METHOD__,
				"unit" => $unit
			));
		}
		return doubleval($seconds / $seconds_in_unit);
	}
	
	/**
	 * Convert seconds into a particular unit
	 *
	 * @param integer $seconds
	 *        	Number of seconds to convert to a unit.
	 * @param string $stop_unit
	 *        	Unit to stop comparing for. If you only want to know how many months away
	 *        	something is,
	 *        	specify a higher value.
	 * @param double $fraction
	 *        	Returns $seconds divided by total units, can be used to specify 2.435 years, for
	 *        	example.
	 * @return string The units closest to the number of seconds
	 */
	public static function seconds_to_unit($seconds, $stop_unit = "second", &$fraction = null) {
		$seconds = intval($seconds);
		$translation = self::units_translation_table();
		foreach ($translation as $unit => $unit_seconds) {
			if (($seconds > $unit_seconds) || ($stop_unit === $unit)) {
				$fraction = intval($seconds / $unit_seconds);
				return $unit;
			}
		}
		$fraction = $seconds;
		return $unit;
	}
}
