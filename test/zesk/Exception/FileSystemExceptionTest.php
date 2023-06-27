<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Exception;

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
		$testx = new FileSystemException($filename, $message, $arguments, $code);

		$this->assertThrowable($testx);
	}
}
