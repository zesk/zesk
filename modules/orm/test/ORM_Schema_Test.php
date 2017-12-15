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
class ORM_Schema_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL",
		"ORM"
	);
	function test_debug() {
		$value = ORM_Schema::debug();
		
		ORM_Schema::debug(true);
		$this->assert_equal(ORM_Schema::debug(), true);
		ORM_Schema::debug("Friday");
		$this->assert_equal(ORM_Schema::debug(), true);
		
		ORM_Schema::debug(false);
		$this->assert_equal(ORM_Schema::debug(), false);
		ORM_Schema::debug("Friday");
		$this->assert_equal(ORM_Schema::debug(), false);
		
		ORM_Schema::debug($value);
	}
	function test_update_objects() {
		$object = $this->application->orm_factory(Test_ORM_Schema_User::class);
		ORM_Schema::update_object($object);
	}
}

/**
 * 
 */
class Class_Test_ORM_Schema_User extends \zesk\Class_User {}

/**
 * 
 */
class Test_ORM_Schema_User extends \zesk\User {}

