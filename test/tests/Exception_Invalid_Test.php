<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_Invalid_Test extends Exception_TestCase {
	public function test_basics() {
		$testx = new Exception_Invalid();
		$this->exception_test($testx);
	}
}
