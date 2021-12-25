<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

class Exception_File_Format_Test extends Exception_TestCase {
	public function test_basics(): void {
		$testx = new Exception_File_Format();
		$this->exception_test($testx);
	}
}
