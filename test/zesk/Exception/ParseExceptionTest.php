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

class ParseExceptionTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$testObject = new ParseException();
		$this->assertThrowable($testObject);
	}
}
