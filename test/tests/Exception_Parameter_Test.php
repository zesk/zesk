<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_Parameter_Test extends Exception_TestCase {
	function test_basics() {
		$testx = new Exception_Parameter();
		$this->exception_test($testx);
	}
}
