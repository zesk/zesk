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

class DirectoryCreateTest extends ExceptionTestCase {
	public function test_basics(): void {
		$testx = new DirectoryCreate(__DIR__);
		$this->assertThrowable($testx);
	}

	/*
	 * TODO Add tests where DirectoryCreate is thrown in zesk
	 */
}
