<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_File_NotFound_Test extends Exception_TestCase {
	function test_basics() {
		$testx = new Exception_File_NotFound();
		$this->exception_test($testx);
	}
}
