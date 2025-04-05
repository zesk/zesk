<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace test\zesk\Exception;

use zesk\PHPUnit\ExceptionTestCase;

class FileParseExceptionTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$testx = new FileParseException();
		$this->assertThrowable($testx);
	}
}
