<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\DirectoryNotFound;
use zesk\PHPUnit\ExceptionTestCase;

class DirectoryNotFoundTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\DirectoryNotFound
	 */
	public function test_basics(): void
	{
		$testObject = new DirectoryNotFound();
		$this->assertThrowable($testObject);
	}
}
