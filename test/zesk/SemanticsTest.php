<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace test\zesk;

use zesk\Exception\ProtocolException;
use zesk\PHPUnit\ExceptionTestCase;

class SemanticsTest extends ExceptionTestCase
{
	public function test_basics(): void
	{
		$exception = new ProtocolException();
		$this->assertThrowable($exception);
	}
}
