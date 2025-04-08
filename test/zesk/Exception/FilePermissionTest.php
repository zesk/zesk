<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace test\zesk\Exception;

use zesk\Exception\FilePermission;
use zesk\PHPUnit\ExceptionTestCase;

class FilePermissionTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$testObject = new FilePermission();
		$this->assertThrowable($testObject);
	}
}
