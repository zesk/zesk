<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\FileSystemException;
use zesk\PHPUnit\ExceptionTestCase;

class FileSystemExceptionTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\FileSystemException
	 */
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
