<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception\SemanticsException;
use zesk\PHPUnit\ExceptionTestCase;

class SemanticsExceptionTestCase extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\SemanticsException
	 */
	public function test_basics(): void
	{
		$exception = new SemanticsException();
		$this->assertThrowable($exception);
	}
}
