<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Session\Session\test;

use ORMUnitTest;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Session\Session\classes\SessionORM;
use zesk\User;
use zesk\zesk;

/**
 * @see SessionORM
 * @author kent
 *
 */
class SessionORM_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL',
		'Session',
	];

	/**
	 * @return void
	 * @throws Exception_Convert
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @expectedException zesk\Exception_Convert
	 */
	public function test_no_userId(): void {
		$testx = new SessionORM($this->application);
		$testx->userId();
	}

	/**
	 * @return void
	 * @throws Exception_Convert
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 */
	public function test_userId(): void {
		$testx = new SessionORM($this->application);
		$testx->setMember('user', 2);
		$testx->userId();
	}

	public function test_main(): void {
		$testx = new SessionORM($this->application);

		$user = new User($this->application);
		$user_table = $user->table();

		$table = $testx->table();

		$db = $testx->database();
		$db->query("DROP TABLE IF EXISTS `$table`");
		$db->query("DROP TABLE IF EXISTS `$user_table`");

		$db->queries($this->application->orm_module()->schema_synchronize($db, [
			User::class,
			SessionORM::class,
		], [
			'follow' => true,
		]));

		//$this->test_an_object($testx, "ID");

		$testx->setMember('cookie', md5(microtime()));
		$user_id = 1;
		$ip = '10.0.0.1';
		$testx->authenticate($user_id, $ip);

		$testx->relinquish();

		$testx->hash();

		$hash = 'ABC';
		$find = SessionORM::one_time_find($this->application, $hash);


		$user = new User($this->application, 1);
		$user->fetch();

		$resx = $testx->one_time_create($user, 2);
		$this->assert($resx instanceof SessionORM);
		$this->assertTrue($resx->memberBool('is_one_time'));
		$this->assertNotEquals($resx->member('cookie'), $testx->member('cookie'));

		$testx->A = 'A';
		$testx->B = 'B';
		$testx->Dog = 'Cat';
		$testx->Cat = 'Dog';
		$testx->Wildebeast = 'Grawp';
		$testx->Wild_thing = 'Grawp1';
		$testx->Wilder_thang = 'Grawp2';

		$result = $testx->filter([
			'A' => 'B',
			'B' => 'A',
			'Dog' => 'Cat-like',
			'Cat' => 'Dog-like',
			'Wilder_thang',
		]);

		$this->assertEquals($result, [
			'A' => 'B',
			'B' => 'A',
			'Cat-like' => 'Cat',
			'Dog-like' => 'Dog',
			'Wilder_thang' => 'Grawp2',
		]);
	}
}
