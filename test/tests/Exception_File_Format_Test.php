<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_File_Format_Test extends Test_Exception {
	function test_basics() {
		$testx = new Exception_File_Format();
		$this->exception_test($testx);
	}
}