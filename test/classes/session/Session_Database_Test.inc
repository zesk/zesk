<?php
class Session_Database_Test extends Test_Object {
	function test_main() {
		$value = null;
		$options = false;
		$testx = new Session_Database($value, $options);

		$table = $testx->table();

		$db = $testx->database();
		$db->query("DROP TABLE IF EXISTS `$table`");
		$db->query($testx->schema());

		//$this->test_an_object($testx, "ID");


		Session_Database::global_cookie_name();

		Session_Database::global_cookie_expire();

		$testx->set_member('cookie', md5(microtime()));
		$user_id = 1;
		$ip = '10.0.0.1';
		$testx->authenticate($user_id, $ip);

		$testx->deauthenticate();

		Session_Database::enabled(false);

		Session_Database::enabled(true);

		Session_Database::enabled();

		Session_Database::instance();

		Session_Database::master_id();

		$testx->hash();

		$hash = "ABC";
		Session_Database::one_time_find($hash);

		$testx->user_id();

		$resx = $testx->one_time_create(2);
		$this->assert($resx instanceof Session_Database);
		$this->assert_true($resx->memberBoolean('is_one_time'));
		$this->assert_not_equal($resx->member('cookie'), $testx->member('cookie'));

		$testx->A = "A";
		$testx->B = "B";
		$testx->Dog = "Cat";
		$testx->Cat = "Dog";
		$testx->Wildebeast = "Grawp";
		$testx->Wild_thing = "Grawp1";
		$testx->Wilder_thang = "Grawp2";

		$result = $testx->filter(array(
			"A" => "B",
			"B" => "A",
			"Dog" => "Cat-like",
			"Cat" => "Dog-like",
			"Wilder_thang"
		));

		$this->assert_arrays_equal($result, array(
			"A" => "B",
			"B" => "A",
			"Cat-like" => "Cat",
			"Dog-like" => "Dog",
			"Wilder_thang" => "Grawp2"
		));

		register_shutdown_function(array(
			$db,
			"query"
		), "DROP TABLE IF EXISTS `$table`");

		echo basename(__FILE__) . ": success\n";
	}
}
