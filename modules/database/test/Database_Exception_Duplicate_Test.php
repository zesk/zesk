<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Exception_Duplicate_Test extends Test_Database_Exception {
	protected $load_modules = array(
		"MySQL",
	);

	public function database() {
		return $this->application->database_registry();
	}

	/**
	 * @expectedException zesk\Database_Exception_Duplicate
	 */
	public function test_throw() {
		throw new Database_Exception_Duplicate($this->database(), "INSERT INTO foo ( id, name ) VALUES ( 4, 'dude' )", "duplicate for primary key id");
	}

	/**
	 */
	public function test_basics() {
		$e = new Database_Exception_Duplicate($this->database(), "INSERT INTO foo ( id, name ) VALUES ( 4, 'dude' )", "duplicate for primary key id");
		$this->_test_exception($e);
	}
}
