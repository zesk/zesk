<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\FileParseException;
use zesk\PHPUnit\ExceptionTestCase;

class FileParseExceptionTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\FileParseException
	 */
	public function test_basics(): void
	{
		$testObject = new FileParseException();
		$this->assertThrowable($testObject);
	}
}
