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

class FileSystemExceptionTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$filename = '/etc';
		$message = 'Nothing';
		$arguments = [
			'hello' => 'world',
		];
		$code = 42;
		$testObject = new FileSystemException($filename, $message, $arguments, $code);

		$this->assertThrowable($testObject);
	}
}
