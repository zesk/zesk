<?php
declare(strict_types=1);
/**
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\PHPUnit\ExceptionTestCase;

/**
 *
 * @author kent
 *
 */
class Exception_Test extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\DirectoryNotFound
	 */
	public function test_exception_directory_not_found(): void
	{
		$x = new DirectoryNotFound(__DIR__);

		$this->assertThrowable($x);
	}

	/**
	 * @return void
	 * @covers \zesk\Exception\DirectoryCreate
	 */
	public function test_exception_directory_create(): void
	{
		$x = new DirectoryCreate(__DIR__);

		$this->assertThrowable($x);
	}
}
