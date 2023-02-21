<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Exception;

use zesk\PHPUnit\ExceptionTestCase;
use zesk\RuntimeException;

class RuntimeException_Test extends ExceptionTestCase {
	public function test_basics(): void {
		$exception = new RuntimeException();
		$this->assertThrowable($exception);
	}
}
