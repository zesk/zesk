<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\DirectoryCreate;
use zesk\PHPUnit\ExceptionTestCase;

class DirectoryCreateTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\DirectoryCreate
	 */
	public function test_basics(): void
	{
		$testObject = new DirectoryCreate(__DIR__);
		$this->assertThrowable($testObject);
	}

	/*
	 * TODO Add tests where DirectoryCreate is thrown in zesk
	 */
}
