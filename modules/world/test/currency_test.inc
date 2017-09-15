<?php
namespace zesk;

class Currency_Test extends Test_Object {
	protected $load_modules = array(
		"Commerce",
		"MySQL"
	);
	function initialize() {
		$this->application->schema_synchronize(null, array(
			__NAMESPACE__ . "\\" . "Currency"
		), array(
			"follow" => true
		));
		parent::initialize();
	}
	function classes_to_test() {
		return array(
			array(
				__NAMESPACE__ . "\\" . "Currency",
				array()
			)
		);
	}
}
