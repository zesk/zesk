<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\ParseException;
use zesk\PHPUnit\ExceptionTestCase;

class ParseExceptionTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\ParseException
	 */
	public function test_basics(): void
	{
		$testObject = new ParseException();
		$this->assertThrowable($testObject);
	}
}
