<?php declare(strict_types=1);
/**
 * @test_sandbox true
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Session_ORM
 * @author kent
 *
 */
class Session_ORM_Test extends Test_ORM {
	protected array $load_modules = [
		"MySQL",
		"Session",
	];

	public function test_main(): void {
		$testx = new Session_ORM($this->application);

		$user = new User($this->application);
		$user_table = $user->table();

		$table = $testx->table();

		$db = $testx->database();
		$db->query("DROP TABLE IF EXISTS `$table`");
		$db->query("DROP TABLE IF EXISTS `$user_table`");

		$db->query($this->application->orm_module()->schema_synchronize($db, [
			User::class,
			Session_ORM::class,
		], [
			"follow" => true,
		]));

		//$this->test_an_object($testx, "ID");

		$testx->set_member('cookie', md5(microtime()));
		$user_id = 1;
		$ip = '10.0.0.1';
		$testx->authenticate($user_id, $ip);

		$testx->deauthenticate();

		$testx->hash();

		$hash = "ABC";
		$find = Session_ORM::one_time_find($this->application, $hash);

		$testx->user_id();

		$user = new User($this->application, 1);
		$user->fetch();

		$resx = $testx->one_time_create($user, 2);
		$this->assert($resx instanceof Session_ORM);
		$this->assert_true($resx->member_boolean('is_one_time'));
		$this->assert_not_equal($resx->member('cookie'), $testx->member('cookie'));

		$testx->A = "A";
		$testx->B = "B";
		$testx->Dog = "Cat";
		$testx->Cat = "Dog";
		$testx->Wildebeast = "Grawp";
		$testx->Wild_thing = "Grawp1";
		$testx->Wilder_thang = "Grawp2";

		$result = $testx->filter([
			"A" => "B",
			"B" => "A",
			"Dog" => "Cat-like",
			"Cat" => "Dog-like",
			"Wilder_thang",
		]);

		$this->assert_arrays_equal($result, [
			"A" => "B",
			"B" => "A",
			"Cat-like" => "Cat",
			"Dog-like" => "Dog",
			"Wilder_thang" => "Grawp2",
		]);
	}
}
