<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\KeyNotFound;
use zesk\PHPUnit\ExceptionTestCase;

class KeyNotFoundTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\KeyNotFound
	 */
	public function test_basics(): void
	{
		$testObject = new KeyNotFound();
		$this->assertThrowable($testObject);
	}
}
