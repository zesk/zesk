<?php

class Preference_Type_Test extends Test_Unit {
	protected $load_modules = array(
		"preference"
	);
	protected function initialize() {
		$this->schema_synchronize("Preference_Type");
	}
	function test_object() {
		$test = new Test_Unit();
		
		$value = null;
		$options = false;
		$x = new Preference_Type($value, $options);
		
		$code_name = "Poore";
		$name = false;
		Preference_Type::register_name($code_name, $name);
	}
}
