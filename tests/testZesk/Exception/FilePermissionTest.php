<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\FilePermission;
use zesk\PHPUnit\ExceptionTestCase;

class FilePermissionTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\FilePermission
	 */
	public function test_basics(): void
	{
		$testObject = new FilePermission();
		$this->assertThrowable($testObject);
	}
}
