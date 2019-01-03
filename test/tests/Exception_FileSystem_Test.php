<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_FileSystem_Test extends Exception_TestCase {
	public function test_basics() {
		$filename = "/etc";
		$message = 'Nothing';
		$arguments = array(
			"hello" => "world",
		);
		$code = 42;
		$testx = new Exception_FileSystem($filename, $message, $arguments, $code);

		$this->exception_test($testx);
	}
}
