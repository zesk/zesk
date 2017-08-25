<?php
namespace zesk;

class World_Test extends Test_Object {
	protected $load_modules = array(
		"World",
		"MySQL"
	);
	function initialize() {
		$db = $this->application->database_factory();
		$this->assert_not_null($db, "Database not connected");
		$this->require_tables(__NAMESPACE__ . "\\" . "Country");
	}
	function classes_to_test() {
		return array(
			array(
				__NAMESPACE__ . "\\" . "City",
				array()
			),
			array(
				__NAMESPACE__ . "\\" . "Country",
				array()
			),
			array(
				__NAMESPACE__ . "\\" . "Province",
				array()
			),
			array(
				__NAMESPACE__ . "\\" . "Currency",
				array()
			)
		);
	}
}
	
