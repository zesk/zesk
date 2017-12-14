<?php
namespace zesk;

class Currency_Test extends Test_ORM {
	protected $load_modules = array(
		"World",
		"ORM",
		"MySQL"
	);
	function initialize() {
		$this->application->schema_synchronize(null, array(
			Currency::class
		), array(
			"follow" => true
		));
		parent::initialize();
	}
	function classes_to_test() {
		return array(
			array(
				Currency::class,
				array()
			)
		);
	}
}
