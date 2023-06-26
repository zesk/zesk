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

class FileCreateTest extends ExceptionTestCase {
	public function test_basics(): void {
		$testx = new FileCreate();
		$this->assertThrowable($testx);
	}
}
