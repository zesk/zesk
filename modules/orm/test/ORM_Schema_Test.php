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
	function initialize() {
		require_once __DIR__ . '/ORM_Schema_Test_Objects.php';
		parent::initialize();
	}
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
