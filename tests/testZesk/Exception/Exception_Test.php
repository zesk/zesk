<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk\Exception;

use zesk\PHPUnit\ExceptionTestCase;

use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryCreate;

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
