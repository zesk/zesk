<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk\Exception;

use zesk\Exception\UnimplementedException;
use zesk\PHPUnit\ExceptionTestCase;

/**
 *
 */
class UnimplementedTest extends ExceptionTestCase
{
	/**
	 * @return void
	 * @covers \zesk\Exception\UnimplementedException
	 */
	public function test_basics(): void
	{
		$testObject = new UnimplementedException();
		$this->assertThrowable($testObject);
	}
}
