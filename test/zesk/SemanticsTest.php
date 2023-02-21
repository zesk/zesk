<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Exception\Semantics;
use zesk\PHPUnit\ExceptionTestCase;

class SemanticsTest extends ExceptionTestCase {
	public function test_basics(): void {
		$exception = new Semantics();
		$this->assertThrowable($exception);
	}
}
