<?php
/**
 * 
 */
namespace zesk;

/**
 * Glue for old tests
 *
 */
class Adapter_TestFramework extends PHPUnit_TestCase implements Interface_Testable {
	
	/**
	 * 
	 * @param boolean $condition
	 * @param string $message
	 */
	final public function assert($condition, $message = null) {
		$this->assertTrue($condition, $message);
	}
	/**
	 * Assert a value is false
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_false($condition, $message = null) {
		return $this->assertFalse($condition, $message);
	}
	
	/**
	 * Assert a value is true
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_true($condition, $message = null) {
		$this->assertTrue($condition, $message);
	}
	
	/**
	 * Assert a value is a string
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_string($mixed, $message = null) {
		$this->assertTrue(is_string($mixed), "!is_string(" . type($mixed) . " $mixed) $message");
	}
	
	/**
	 * Assert a value is numeric
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_numeric($mixed, $message = null) {
		$this->assertTrue(is_numeric($mixed), "!is_numeric(" . type($mixed) . " $mixed) $message");
	}
	
	/**
	 * Assert a value is an integer
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_integer($mixed, $message = null) {
		$this->assertTrue(is_integer($mixed), "!is_integer(" . type($mixed) . " $mixed) $message");
	}
	
	/**
	 * Assert a value is an array
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_is_array($mixed, $message = null) {
		$this->assertTrue(is_array($mixed), "!is_array(" . type($mixed) . ") $message");
	}
	
	/**
	 * Assert a value is an instanceof a class
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_instanceof($mixed, $instanceof, $message = null) {
		$this->assertInstanceOf($mixed, $instanceof, $message);
	}
	final public function assert_class_exists($class, $message = null) {
		$this->assert_is_string($class, "Class passed to " . __METHOD__ . " should be string");
		$default_message = "Asserted class $class exists when it does not";
		try {
			$this->assertTrue(class_exists($class), $message ? $message : $default_message);
		} catch (Exception_Class_NotFound $e) {
			$this->assertTrue(false, $message ? $message : $default_message);
		}
	}
	/**
	 * Assert a value is an instanceof a class
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_implements($mixed, $instanceof, $message = null) {
		$interfaces = class_implements($mixed);
		$this->assert(in_array($instanceof, $interfaces), "!" . type($mixed) . " implements $instanceof (does implement " . implode(", ", $interfaces) . ") $message", false);
	}
	
	/**
	 * Assert a value is a positive number
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_positive($value, $message = null) {
		$this->assertTrue($value > 0, "$value > 0 : $message", false);
	}
	
	/**
	 * Assert a value is not NULL
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_not_null($value, $message = null) {
		$this->assertTrue($value !== null, "Asserted not NULL failed: $message", false);
	}
	
	/**
	 * Assert a value is a negative number
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_negative($value, $message = null) {
		$this->assertTrue($value < 0, "$value < 0 : $message", false);
	}
	
	/**
	 * Assert a value is null
	 *
	 * @param mixed $mixed
	 * @param string $message
	 */
	final public function assert_null($value, $message = null) {
		$this->assertTrue($value === null, "$value === null : $message", false);
	}
	
	/**
	 * Assert two arrays are equal
	 *
	 * @param array $actual
	 * @param array $expected
	 * @param string $messageassertTrue
	 * @param boolean $strict
	 */
	final protected function assert_arrays_equal($actual, $expected, $message = null, $strict = true) {
		$this->assertTrue(is_array($actual), gettype($actual) . " is not an array");
		$this->assertTrue(is_array($expected), gettype($expected) . " is not an array");
		$this->assert_equal($actual, $expected, $message, $strict);
	}
	final protected function assert_array_key_exists(array $array, $key, $message = null) {
		if ($message === null) {
			$message = "Array does not contain key: $key (keys: " . implode(", ", array_keys($array)) . ")";
		}
		$this->assert(array_key_exists($key, $array), $message);
	}
	final protected function assert_array_key_not_exists(array $array, $key, $message = null) {
		if ($message === null) {
			$message = "Array does contain key but should not: $key (keys: " . implode(", ", array_keys($array)) . ")";
		}
		$this->assert(!array_key_exists($key, $array), $message);
	}
	final protected function assert_in_array(array $array, $mixed, $message = null) {
		if ($message === null) {
			$message = "Array does not contain value: $mixed (values: " . implode(", ", array_values($array)) . ")";
		}
		$this->assert(in_array($mixed, $array), $message);
	}
	final protected function assert_not_in_array(array $array, $mixed, $message = null) {
		if ($message === null) {
			$message = "Array should not contain value: $mixed (values: " . implode(", ", array_values($array)) . ")";
		}
		$this->assert(!in_array($mixed, $array), $message);
	}
	final protected function assert_contains($haystack, $needle, $message = null) {
		if ($message === null) {
			$message = "$haystack\n=== DOES NOT CONTAIN STRING===\n$needle";
		}
		$this->assert(strpos($haystack, $needle) !== false, $message);
	}
	final protected function assert_string_begins($haystack, $needle, $message = null) {
		if ($message === null) {
			$message = "$haystack\n=== DOES NOT BEGIN WITH STRING===\n$needle";
		}
		$this->assert(strpos($haystack, $needle) === 0, $message);
	}
	final protected function assert_equal($actual, $expected, $message = null, $strict = true) {
		$this->assertEquals($expected, $actual, $message);
		$message .= "\nassert_equal failed:\n  Actual: " . gettype($actual) . ": " . $this->dump($actual) . "\nExpected: " . gettype($expected) . ": " . $this->dump($expected);
		if (is_scalar($actual) && is_scalar($expected)) {
			if (is_double($actual) && is_double($expected)) {
				if (abs($actual - $expected) > 0.00001) {
					$this->fail($message);
				}
			} else if ($strict) {
				$this->assert($actual === $expected, $message);
			} else {
				$this->assert($actual == $expected, $message);
			}
		} else if (is_array($actual) && is_array($expected)) {
			$this->assert_equal_array($actual, $expected, $message, $strict);
		} else if (is_object($actual) && is_object($expected)) {
			$this->assert_equal_object($actual, $expected, $message, $strict);
		} else if (is_null($actual) && is_null($expected)) {
			return;
		} else {
			$this->fail("Unhandled or mismatched types: $message");
		}
	}
	final protected function assert_not_equal($actual, $expected, $message = null, $strict = true) {
		if ($message === null) {
			$message = gettype($actual) . ": " . $this->dump($actual) . " === " . gettype($expected) . ": " . $this->dump($expected);
		}
		if ($strict) {
			$this->assert($actual !== $expected, $message);
		} else {
			$this->assert($actual != $expected, $message);
		}
	}
	public final function assert_equal_object($actual, $expected, $message = "") {
		$this->assert(get_class($actual) === get_class($expected), $message . "get_class(" . get_class($actual) . ") === get_class(" . get_class($expected) . ")");
		$this->assert($actual == $expected, $message . "\n" . $this->dump($actual) . " !== " . $this->dump($expected));
	}
	/**
	 * Central place to dump variables to output.
	 * Use PHP output to facilitate generating tests whose output can be copied for first writing
	 * and manual verification.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function dump($value) {
		return PHP::singleton()->settings_one()->render($value);
	}
	final protected function assert_equal_array($actual, $expected, $message = "", $strict = true, $order_matters = false) {
		if (!is_array($actual)) {
			$this->fail("$message: \$actual is not an array: " . $this->dump($actual, false));
		}
		if (!is_array($expected)) {
			$this->fail("$message: \$expected is not an array: " . $this->dump($expected, false));
		}
		if (count($actual) !== count($expected)) {
			$this->fail("$message: Arrays are diferent sizes: count(\$actual)=" . count($actual) . " count(\$expected)=" . count($expected));
		}
		foreach ($actual as $k => $v) {
			if (!array_key_exists($k, $expected)) {
				$this->fail("$message: $k doesn't exist in 2nd array");
			}
			if ($strict && gettype($v) !== gettype($expected[$k])) {
				$this->fail("$message: types do not match for key $k: $v(" . gettype($v) . ") !== " . $expected[$k] . "(" . gettype($expected[$k]) . ")");
			}
			if (is_array($v)) {
				$this->assert_equal($v, $expected[$k], "[$k] $message", $strict);
			} else if (is_object($v)) {
				$this->assert(get_class($v) === get_class($expected[$k]), "Classes don't match " . get_class($v) . " === " . get_class($expected[$k]) . ": $message");
				$this->assert_equal($v, $expected[$k], "Comparing Key($k) => ");
			} else if ($strict) {
				if ($v !== $expected[$k]) {
					$this->fail("$message: $k doesn't match: $v !== " . $expected[$k]);
				}
			} else {
				if ($v != $expected[$k]) {
					$this->fail("$message: $k doesn't match: $v !== " . $expected[$k]);
				}
			}
		}
		if ($order_matters) {
			$this->assert(implode(";", array_keys($actual)) === implode(";", array_keys($expected)), "Ordering is different: " . implode(";", array_keys($actual)) === implode(";", array_keys($expected)));
		}
	}
	final protected function assert_array_contains($subset, $superset, $message = "") {
		if (!is_array($subset)) {
			$this->fail("$message: \$subset is not an array: " . $this->dump($subset, false));
		}
		if (!is_array($superset)) {
			$this->fail("$message: \$superset is not an array: " . $this->dump($superset, false));
		}
		foreach ($subset as $k => $v) {
			$this->assert(array_key_exists($k, $superset), "$message: Key exists in superset $k (subset value=$v)");
			if (is_array($v)) {
				$this->assert_arrays_equal($v, $superset[$k], "$message: Key $k in array");
			} else {
				$this->assert($superset[$k] === $v, "key $k: $superset[$k] !== $v");
			}
		}
	}
}