<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_Range_Test extends Exception_TestCase {
	public function test_basics() {
		$testx = new Exception_Range();
		$this->exception_test($testx);
	}
}
