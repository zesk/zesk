<?php
class world_test extends Test_Object {
	protected $load_modules = array(
		"world"
	);
	function __construct() {
		parent::__construct();
		$db = $this->application->database_factory();
		$this->assert_not_null($db, "Database not connected");
		$this->require_tables("Country");
	}
	function classes_to_test() {
		return array(
			array(
				"City",
				array()
			),
			array(
				"Country",
				array()
			),
			array(
				"Province",
				array()
			),
			array(
				"Currency",
				array()
			)
		);
	}
}
	
