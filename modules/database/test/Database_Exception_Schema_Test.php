<?php declare(strict_types=1);
namespace zesk;

class Database_Exception_Schema_Test extends Test_Database_Exception {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$e = new Database_Exception_Schema($this->database());
		$this->_test_exception($e);
	}
}
