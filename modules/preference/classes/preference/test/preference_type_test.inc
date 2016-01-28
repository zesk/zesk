<?php
class Preference_Type_Test extends Test_Unit {

	protected function initialize() {
		Module::load("preference");
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
