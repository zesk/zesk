<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Mapping from old Zesk Test to PHPUnit tests as we migrate to PHPUNit
 *
 * @author kent
 * @deprecated 2022 Use PHPUnit
 */
class Deprecated_Adapter_TestFramework extends Test implements Interface_Testable {
	public function assertArrayHasKey(int|string $key, array $array, string $message = ''): void {
		$this->assertTrue(array_key_exists($key, $array), $message ?: 'Array (keys: \'' . implode('\', \'', array_keys($array)) . "') missing key $key");
	}

	public function assertArrayHasKeys(array $keys, array $array, string $message = ''): void {
		foreach ($keys as $key) {
			$this->assertTrue(array_key_exists($key, $array), $message ?: 'Array (keys: \'' . implode('\', \'', array_keys($array)) . "') missing key $key");
		}
	}

	public function assertEquals($expected, $actual, $message = null): void {
		$this->assertEquals($expected, $actual, $message);
	}

	public function assertNotEquals($expected, $actual, $message = null): void {
		$this->assertNotEquals($expected, $actual, $message);
	}

	public function assertInstanceOf($expectedClass, $thing, $message = null): void {
		$this->assert_instanceof($thing, $expectedClass, $message);
	}

	public function assertTrue($condition, $message = null): void {
		$this->assertTrue($condition, $message);
	}

	public function assertNull($value, $message = null): void {
		$this->assertNull($value, $message);
	}

	public function assertNotNull($value, $message = null): void {
		$this->assertNotNull($value, $message);
	}

	public function assertFalse($condition, $message = null): void {
		$this->assertFalse($condition, $message);
	}

	public function assertIsString($actual, $message = null): void {
		$this->assertTrue(is_string($actual), 'Expected string but received ' . type($actual) . " $message");
	}

	public function assertContains($needle, $haystack, $message = null): void {
		$this->assertTrue(str_contains($haystack, $needle), "Unable to find needle \"$needle\" in haystack:\n$haystack\n");
	}

	public function assertIsArray($object, $message = null) {
		return $this->assertIsArray($object, $message);
	}

	public function assertNotEmpty($mixed, $message = null) {
		return $this->assertFalse(empty($mixed), $message);
	}

	public function assertEmpty($mixed, $message = null) {
		return $this->assertTrue(empty($mixed), $message);
	}
}
