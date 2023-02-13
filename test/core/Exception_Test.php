<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

use zesk\PHPUnit\ExceptionTestCase;

/**
 *
 * @author kent
 *
 */
class Exception_Test extends ExceptionTestCase {
	public function test_exception_directory_not_found(): void {
		$x = new Exception_Directory_NotFound(ZESK_ROOT);

		$this->exception_test($x);
	}

	public function test_exception_directory_create(): void {
		$x = new Exception_Directory_Create(ZESK_ROOT);

		$this->exception_test($x);
	}
}
