<?php
class Currency_Test extends Test_Object {

	function initialize() {
		Module::load('commerce');
		Application::instance()->schema_synchronize(null, array("Currency"));
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
