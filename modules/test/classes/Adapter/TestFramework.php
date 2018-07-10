<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Mapping from old Zesk Test to PHPUnit tests as we migrate to PHPUNit
 *
 * @author kent
 */
class Adapter_TestFramework extends Test implements Interface_Testable {
	function assertEquals($expected, $actual, $message = null) {
		$this->assert_equal($actual, $expected, $message);
	}
	function assertInstanceOf($expected, $actual, $message = null) {
		$this->assert_instanceof($actual, $expected, $message);
	}
	function assertTrue($condition, $message = null) {
		$this->assert_true($condition, $message);
	}
	function assertFalse($condition, $message = null) {
		$this->assert_false($condition, $message);
	}
	function assertIsString($actual, $message = null) {
		$this->assertTrue(is_string($actual), "Expected string but received " . type($actual) . " $message");
	}
}