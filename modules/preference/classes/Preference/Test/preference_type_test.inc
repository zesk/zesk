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
		"Preference",
		"MySQL",
	);

	protected function initialize() {
		$this->schema_synchronize(Preference_Type::class);
	}

	public function test_object() {
		$x = $this->application->model_factory(Preference_Type::class);

		$code_name = "Poore";
		$name = false;
		Preference_Type::register_name($this->application, $code_name, $name);
	}
}
