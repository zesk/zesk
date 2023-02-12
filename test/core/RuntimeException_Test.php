<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class RuntimeException_Test extends Exception_TestCase {
	public function test_basics(): void {
		$exception = new RuntimeException();
		$this->exception_test($exception);
	}
}
