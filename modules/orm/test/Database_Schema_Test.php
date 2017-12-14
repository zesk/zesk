<?php
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Database_Schema_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL"
	);
	function test_debug() {
		$value = Database_Schema::debug();
		
		Database_Schema::debug(true);
		$this->assert_equal(Database_Schema::debug(), true);
		Database_Schema::debug("Friday");
		$this->assert_equal(Database_Schema::debug(), true);
		
		Database_Schema::debug(false);
		$this->assert_equal(Database_Schema::debug(), false);
		Database_Schema::debug("Friday");
		$this->assert_equal(Database_Schema::debug(), false);
		
		Database_Schema::debug($value);
	}
	function test_update_objects() {
		$object = $this->application->orm_factory(__NAMESPACE__ . "\\" . "Test_Database_Schema_User");
		Database_Schema::update_object($object);
	}
}
class Class_Test_Database_Schema_User extends \zesk\Class_User {}
class Test_Database_Schema_User extends \zesk\User {}

