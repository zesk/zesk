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
class Preference_Type_Test extends Test_Unit {
	protected $load_modules = array(
		"preference",
		"MySQL"
	);
	protected function initialize() {
		$this->schema_synchronize(__NAMESPACE__ . "\\" . "Preference_Type");
	}
	function test_object() {
		$x = $this->application->object_factory(__NAMESPACE__ . "\\" . "Preference_Type");
		
		$code_name = "Poore";
		$name = false;
		Preference_Type::register_name($this->application, $code_name, $name);
	}
}

