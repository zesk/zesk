<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Exception;

use zesk\PHPUnit\ExceptionTestCase;

/**
 *
 * @author kent
 *
 */
class Exception_Test extends ExceptionTestCase {
	public function test_exception_directory_not_found(): void {
		$x = new DirectoryNotFound(__DIR__);

		$this->assertThrowable($x);
	}

	public function test_exception_directory_create(): void {
		$x = new DirectoryCreate(__DIR__);

		$this->assertThrowable($x);
	}
}
