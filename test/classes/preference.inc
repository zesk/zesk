<?php
class Test_Preference extends Test_Object {
	function test_get() {
		$user = new User();
		$name = null;
		$default = false;
		Preference::user_get($user, $name, $default);
	}
	function test_getBoolean() {
		$name = null;
		$default = false;
		Preference::getBoolean($name, $default);
	}
	function test_getDate() {
		$name = null;
		$default = false;
		Preference::getDate($name, $default);
	}
	function test_getInteger() {
		$name = null;
		$default = false;
		Preference::getInteger($name, $default);
	}
	function test_set() {
		$name = null;
		$value = false;
		Preference::_set($name, $value);
	}
	function test_userGet() {
		$user = new User();
		$name = null;
		$default = false;
		Preference::userGet($user, $name, $default);
	}
	function test_userSet() {
		$user = new User();
		$name = null;
		$value = false;
		Preference::userSet($user, $name, $value);
	}
	function test_Preference() {
		$db = Database::instance();
		$db->query(Application::instance()->schema_synchronize($db, array(
			"Preference",
			"Preference_Type"
		)));
		$value = null;
		$options = true;
		$testx = new Preference($value, $options);

		$this->test_an_object($testx, "PreferenceType");

		$user = new User();
		$name = null;
		$default = false;
		Preference::user_get($user, $name, $default);

		$name = null;
		$default = false;
		Preference::getDate($name, $default);

		$name = null;
		$default = false;
		Preference::getBoolean($name, $default);

		$name = null;
		$default = false;
		Preference::getInteger($name, $default);

		$name = null;
		$value = false;
		Preference::userSet($user, $name, $value);
	}
}
