<?php
class world_test extends Test_Object {
	function __construct() {
		parent::__construct();
		$this->assert_equal(avalue(Module::load('world'), 'loaded'), true);
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
	
