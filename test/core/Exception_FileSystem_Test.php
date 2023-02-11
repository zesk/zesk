<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class Exception_FileSystem_Test extends Exception_TestCase {
	public function test_basics(): void {
		$filename = '/etc';
		$message = 'Nothing';
		$arguments = [
			'hello' => 'world',
		];
		$code = 42;
		$testx = new Exception_FileSystem($filename, $message, $arguments, $code);

		$this->exception_test($testx);
	}
}
