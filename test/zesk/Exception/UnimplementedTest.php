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

class UnimplementedTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$testx = new UnimplementedException();
		$this->assertThrowable($testx);
	}
}
