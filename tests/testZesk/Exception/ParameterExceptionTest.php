<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\PHPUnit\ExceptionTestCase;

use zesk\Exception\ParameterException;

class ParameterExceptionTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\ParameterException
	 */
	public function test_basics(): void
	{
		$testObject = new ParameterException();
		$this->assertThrowable($testObject);
	}
}
