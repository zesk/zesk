<?php
class Currency_Test extends Test_Object {
	protected $load_modules = array(
		"commerce"
	);
	function initialize() {
		Application::instance()->schema_synchronize(null, array(
			"Currency"
		));
		parent::initialize();
	}
	function classes_to_test() {
		return array(
			array(
				"Currency",
				array()
			)
		);
	}
}
