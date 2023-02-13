<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use zesk\PHPUnit\ExceptionTestCase;

class RuntimeException_Test extends ExceptionTestCase {
	public function test_basics(): void {
		$exception = new RuntimeException();
		$this->exception_test($exception);
	}
}
