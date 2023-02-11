<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Exception_Directory_Create_Test extends Exception_TestCase {
	public function test_basics(): void {
		$testx = new Exception_Directory_Create();
		$this->exception_test($testx);
	}
}
