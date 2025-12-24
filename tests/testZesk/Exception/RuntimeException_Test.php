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
use zesk\RuntimeException;

class RuntimeException_Test extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\RuntimeException
	 */
	public function test_basics(): void
	{
		$exception = new RuntimeException();
		$this->assertThrowable($exception);
	}
}
