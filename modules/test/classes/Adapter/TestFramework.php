<?php declare(strict_types=1);
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
	public function assertEquals($expected, $actual, $message = null): void {
		$this->assert_equal($expected, $actual, $message);
	}

	public function assertInstanceOf($expectedClass, $thing, $message = null): void {
		$this->assert_instanceof($thing, $expectedClass, $message);
	}

	public function assertTrue($condition, $message = null): void {
		$this->assert_true($condition, $message);
	}

	public function assertNull($value, $message = null): void {
		$this->assert_null($value, $message);
	}

	public function assertNotNull($value, $message = null): void {
		$this->assert_not_null($value, $message);
	}

	public function assertFalse($condition, $message = null): void {
		$this->assert_false($condition, $message);
	}

	public function assertIsString($actual, $message = null): void {
		$this->assertTrue(is_string($actual), "Expected string but received " . type($actual) . " $message");
	}

	public function assertContains($needle, $haystack, $message = null): void {
		$this->assertTrue(str_contains($haystack, $needle), "Unable to find needle \"$needle\" in haystack:\n$haystack\n");
	}
}
