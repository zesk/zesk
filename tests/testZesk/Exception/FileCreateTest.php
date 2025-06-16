<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\FileCreate;
use zesk\PHPUnit\ExceptionTestCase;

class FileCreateTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\FileCreate
	 */
	public function test_basics(): void
	{
		$testObject = new FileCreate();
		$this->assertThrowable($testObject);
	}
}
