<?php
/**
 * @test_sandbox true
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

class Session_Database_Test extends Test_Object {
	protected $load_modules = array(
		"MySQL"
	);
	function test_main() {
		$value = null;
		$options = false;
		$testx = new Session_Database($this->application, $value, $options);
		
		$user = new User($this->application);
		$user_table = $user->table();
		
		$table = $testx->table();
		
		$db = $testx->database();
		$db->query("DROP TABLE IF EXISTS `$table`");
		$db->query("DROP TABLE IF EXISTS `$user_table`");
		
		$db->query($this->application->schema_synchronize($db, array(
			__NAMESPACE__ . "\\" . "User",
			__NAMESPACE__ . "\\" . "Session_Database"
		), array(
			"follow" => true
		)));
		
		//$this->test_an_object($testx, "ID");
		
		$testx->set_member('cookie', md5(microtime()));
		$user_id = 1;
		$ip = '10.0.0.1';
		$testx->authenticate($user_id, $ip);
		
		$testx->deauthenticate();
		
		$testx->hash();
		
		$hash = "ABC";
		$find = Session_Database::one_time_find($this->application, $hash);
		
		$testx->user_id();
		
		$user = new User($this->application, 1);
		$user->fetch();
		
		$resx = $testx->one_time_create($user, 2);
		$this->assert($resx instanceof Session_Database);
		$this->assert_true($resx->member_boolean('is_one_time'));
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
	}
}
