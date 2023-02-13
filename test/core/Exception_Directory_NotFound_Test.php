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

class Exception_Directory_NotFound_Test extends ExceptionTestCase {
	public function test_basics(): void {
		$testx = new Exception_Directory_NotFound();
		$this->exception_test($testx);
	}
}
