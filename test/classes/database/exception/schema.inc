<?php
namespace zesk;

class Test_Database_Exception_Schema extends Test_Database_Exception {
	protected $load_modules = array(
		"MySQL"
	);
	public function test_main() {
		$e = new Database_Exception_Schema($this->database());
		$this->_test_exception($e);
	}
}
