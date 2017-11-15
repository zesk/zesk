<?php

/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2016 Market Acumen, Inc.
 *
 * http://www.ietf.org/rfc/rfc2445.txt
 *
 * Based on python-dateutil rrule.py, just like everyone else's version
 *
 * https://pypi.python.org/pypi/python-dateutil/2.5.3
 */
namespace RRule;

use zesk\arr;
use zesk\Timestamp;
use zesk\Exception_Syntax;
use zesk\Exception_Parameter;

class Parser {
	/**
	 *
	 * @var array
	 */
	protected $errors = array();
	/**
	 *
	 * @param unknown $s
	 * @param Timestamp $dtstart
	 * @param boolean $unfold
	 * @throws Exception_Syntax
	 * @return \RRule\Set
	 */
	function parse($s, Timestamp $dtstart = null, array $options = array()) {
		$this->errors = array();
		$s = trim(strtoupper($s));
		if (empty($s)) {
			throw new Exception_Syntax("Empty string");
		}
		$s = strtr($s, "\t", " ");
		$lines = explode("\n", $s);
		$unfold = avalue($options, "unfold", true);
		if ($unfold) {
			$lines = $this->unfold_lines($lines);
		}
		$rrulevals = array();
		$rdatevals = array();
		$exrulevals = array();
		$exdatevals = array();
		foreach ($lines as $line) {
			$line = trim($line);
			if (!$line) {
				continue;
			}
			list($name, $value) = pair($line, ":", "RRULE", $line);
			$parameters = arr::clean(explode(";", $name), "");
			if (count($parameters) === 0) {
				$this->errors[] = __(__CLASS__ . ":=Empty property name: {line}", compact("line"));
				continue;
			}
			$name = array_shift($parameters);
			if ($name == "RRULE") {
				if (count($parameters) > 0) {
					throw new Exception_Syntax("unsupported RRULE parameters: {parms}", array(
						"parms" => $parameters
					));
				}
				$rrulevals[] = $value;
			} else if ($name == "RDATE") {
				foreach ($parameters as $parameter) {
					if ($parameter != "VALUE=DATE-TIME") {
						throw new Exception_Syntax("unsupported RDATE parameter: {parm}", array(
							"parm" => $parameter
						));
					}
				}
				$rdatevals[] = $value;
			} else if ($name == "EXRULE") {
				if (count($parameters) > 0) {
					throw new Exception_Syntax("unsupported EXRULE parameters: {parms}", array(
						"parms" => $parameters
					));
				}
				$exrulevals[] = $value;
			} else if ($name == "EXDATE") {
				$time_zone = null;
				foreach ($parameters as $param) {
					if ($param === "VALUE=DATE-TIME") {
						continue;
					}
					list($param_name, $param_value) = pair($param, "=", $param);
					if ($param_name === "TZID") {
						$time_zone = strtr($param_value, '-', '/');
						continue;
					}
					throw new Exception_Syntax("unsupported DTSTART parameters: {parms}", array(
						"parms" => $parameters
					));
				}
				$exdatevals[] = Timestamp::factory($value, $time_zone);
			} else if ($name == "DTSTART") {
				$time_zone = null;
				foreach ($parameters as $param) {
					list($param_name, $param_value) = pair($param, "=", $param);
					if ($param_name === "TZID") {
						$time_zone = strtr($param_value, '-', '/');
						continue;
					}
					throw new Exception_Syntax("unsupported DTSTART parameters: {parms}", array(
						"parms" => $parameters
					));
				}
				$dtstart = Timestamp::factory($value, $time_zone);
			} else {
				throw new Exception_Syntax("unsupported property: {name}", array(
					"name" => $name
				));
			}
		}
		if (!$dtstart) {
			$dtstart = Timestamp::now();
		}
		$rset = new Set();
		
		$this->add_rules_mixed($rset, $dtstart, $rrulevals, "rrule");
		$this->add_timestamps_mixed($rset, $rdatevals, "rdate", $time_zone);
		$this->add_rules_mixed($rset, $dtstart, $exrulevals, "exrule");
		$this->add_timestamps_mixed($rset, $exdatevals, "exdate", $time_zone);
		
		return $rset;
	}
	
	/**
	 *
	 * @param Set $rset
	 * @param Timestamp $dtstart
	 * @param array $values
	 * @param unknown $method
	 */
	private function add_rules_mixed(Set $rset, Timestamp $dtstart, array $values, $method) {
		foreach ($values as $value) {
			$rule = Rule_Parser::factory($value)->dtstart($dtstart)->rule();
			$rset->$method($rule);
		}
	}
	/**
	 * Add a mixed list of timestamps/integers/strings to
	 *
	 * @param Set $rset
	 * @param array $values
	 * @param string $method
	 * @throws Exception_Syntax
	 */
	private function add_timestamps_mixed(Set $rset, array $values, $method, $time_zone = null) {
		foreach ($values as $value) {
			foreach (to_list($value) as $mixed) {
				if ($mixed instanceof Timestamp) {
					$rset->$method($value);
				} else if (is_string($mixed) || is_numeric($mixed)) {
					$ts = Timestamp::factory($mixed, $time_zone);
					if (!$ts->is_empty()) {
						throw new Exception_Syntax("{tag} value of {value} is not a valid date (empty timestamp)", array(
							"tag" => strtoupper($method),
							"value" => $mixed
						));
					} else {
						$rset->$method($mixed);
					}
				} else {
					throw new Exception_Parameter("Unknown type {type} passed to {method}(\$set, {nvalues}, \"{param_method}\")", array(
						"method" => __METHOD__,
						"nvalues" => "Array of " . count($values),
						"type" => type($mixed),
						"param_method" => $method
					));
				}
			}
		}
	}
	/**
	 *
	 * @param array $lines
	 * @return string[]
	 */
	private function unfold_lines(array $lines) {
		$newlines = array();
		foreach ($lines as $line) {
			$line = rtrim($line);
			if (empty($line)) {
				continue;
			}
			if ($line[0] === " ") {
				$n = count($newlines);
				if ($n > 0) {
					$newlines[$n - 1] .= $line;
				}
			} else {
				$newlines[] = $line;
			}
		}
		return $newlines;
	}
}
