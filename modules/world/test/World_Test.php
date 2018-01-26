<?php
namespace zesk;

class World_Test extends Test_ORM {
	protected $load_modules = array(
		"World",
		"MySQL"
	);
	function initialize() {
		$db = $this->application->database_registry();
		$this->assert_not_null($db, "Database not connected");
		$this->require_tables(__NAMESPACE__ . "\\" . "Country");
	}
	function classes_to_test() {
		return array(
			array(
				City::class,
				array()
			),
			array(
				Country::class,
				array()
			),
			array(
				Province::class,
				array()
			),
			array(
				Currency::class,
				array()
			)
		);
	}
}
	
