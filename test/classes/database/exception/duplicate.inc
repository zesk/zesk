<?php
class Test_Database_Exception_Duplicate extends Test_Database_Exception {
	function database() {
		return $this->application->database_factory();
	}
	/**
	 * @expected_exception Test_Database_Exception_Duplicate
	 */
	function test_throw() {
		throw new Database_Exception_Duplicate($this->database(), "INSERT INTO foo ( id, name ) VALUES ( 4, 'dude' )", 1234, "duplicate for primary key id");
	}
	/**
	 */
	function test_basics() {
		$e = new Database_Exception_Duplicate($this->database(), "INSERT INTO foo ( id, name ) VALUES ( 4, 'dude' )", 1234, "duplicate for primary key id");
		$this->_test_exception($e);
	}
}
