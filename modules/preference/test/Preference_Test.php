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
class Preference_Test extends Test_ORM {
	protected $load_modules = array(
		"Preference",
		'MySQL',
	);

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_get() {
		$user = new User($this->application);
		$name = "";
		$default = false;
		Preference::user_get($user, $name, $default);
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_user_get() {
		$user = new User($this->application);
		$name = null;
		$default = false;
		Preference::user_get($user, $name, $default);
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_user_set() {
		$user = new User($this->application);
		$name = null;
		$value = false;
		Preference::user_set($user, $name, $value);
	}

	public function test_Preference() {
		$preference_class = __NAMESPACE__ . "\\" . "Preference";

		$db = $this->application->database_registry();
		$db->query($this->application->orm_module()->schema_synchronize($db, array(
			$preference_class,
			__NAMESPACE__ . "\\" . "Preference_Type",
		), array(
			"follow" => true,
		)));

		$user = new User($this->application, 1);

		Preference::user_set($user, "country", "Random");

		$pref = new Preference($this->application, array(
			"user" => $user,
			"type" => Preference_Type::register_name($this->application, "country"),
		));
		$result = $pref->find();
		$this->assert_equal($result, $pref);
		$this->assert_instanceof($result, $preference_class);
		$this->run_test_an_object($pref);

		$result = $this->application->orm_registry($preference_class)
			->query_delete()
			->where("user", 1)
			->execute();
		$this->assert_instanceof($result, __NAMESPACE__ . "\\" . "Database_Query_Delete");
		$this->log("Deleted {n} rows from {class}", array(
			"n" => $result->affected_rows(),
			"class" => $preference_class,
		));

		$name = "test";
		$default = "Monkey";
		$this->assert_equal(Preference::user_get($user, $name, $default), $default);

		Preference::user_set($user, $name, "Ape");
		$this->assert_equal(Preference::user_get($user, $name, $default), "Ape");
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_Preference_set_parameter() {
		$user = new User($this->application, 1);
		Preference::user_set($user, null, "Ape");
	}
}
