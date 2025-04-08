<?php
declare(strict_types=1);
//declare(strict_types=1);
///**
// * @package zesk
// * @subpackage test
// * @copyright &copy; 2023, Market Acumen, Inc.
// */
//
//namespace test\zesk\Session;
//
//use zesk;
//use zesk\Exception\KeyNotFound;
//use zesk\Exception\ParseException;
//use zesk\ORM\ORMUnitTest;
//use zesk\ORM\User;
//
///**
// * @see Session
// * @author kent
// *
// */
//class SessionORMTest extends ORMUnitTest
//{
//	protected array $load_modules = [
//		'Session',
//	];
//
//	/**
//	 * @return void
//	 * @throws ParseException
//	 * @throws Deprecated
//	 * @throws KeyNotFound
//	 * @expectedException zesk\ParseException
//	 */
//	public function test_no_userId(): void
//	{
//		$testObject = new Session($this->application);
//		$testObject->userId();
//	}
//
//	/**
//	 * @return void
//	 * @throws ParseException
//	 * @throws Deprecated
//	 * @throws KeyNotFound
//	 */
//	public function test_userId(): void
//	{
//		$testObject = new Session($this->application);
//		$testObject->setMember('user', 2);
//		$testObject->userId();
//	}
//
//	public function test_main(): void
//	{
//		$testObject = new Session($this->application);
//
//		$user = new User($this->application);
//		$user_table = $user->table();
//
//		$table = $testObject->table();
//
//		$db = $testObject->database();
//		$db->query("DROP TABLE IF EXISTS `$table`");
//		$db->query("DROP TABLE IF EXISTS `$user_table`");
//
//		$db->queries($this->application->ormModule()->schema_synchronize($db, [
//			User::class,
//			Session::class,
//		], [
//			'follow' => true,
//		]));
//
//		//$this->test_an_object($testObject, "ID");
//
//		$testObject->setMember('cookie', md5(microtime()));
//		$user_id = 1;
//		$ip = '10.0.0.1';
//		$testObject->authenticate($user_id, $ip);
//
//		$testObject->relinquish();
//
//		$testObject->hash();
//
//		$hash = 'ABC';
//		$find = Session::one_time_find($this->application, $hash);
//
//
//		$user = new User($this->application, 1);
//		$user->fetch();
//
//		$resx = $testObject->one_time_create($user, 2);
//		$this->assert($resx instanceof Session);
//		$this->assertTrue($resx->memberBool('is_one_time'));
//		$this->assertNotEquals($resx->member('cookie'), $testObject->member('cookie'));
//
//		$testObject->A = 'A';
//		$testObject->B = 'B';
//		$testObject->Dog = 'Cat';
//		$testObject->Cat = 'Dog';
//		$testObject->Wildebeast = 'Grawp';
//		$testObject->Wild_thing = 'Grawp1';
//		$testObject->Wilder_thang = 'Grawp2';
//
//		$result = $testObject->filter([
//			'A'   => 'B',
//			'B'   => 'A',
//			'Dog' => 'Cat-like',
//			'Cat' => 'Dog-like',
//			'Wilder_thang',
//		]);
//
//		$this->assertEquals($result, [
//			'A'            => 'B',
//			'B'            => 'A',
//			'Cat-like'     => 'Cat',
//			'Dog-like'     => 'Dog',
//			'Wilder_thang' => 'Grawp2',
//		]);
//	}
//}
