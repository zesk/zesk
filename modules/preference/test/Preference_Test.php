<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Preference_Test extends ORMUnitTest {
	protected array $load_modules = [
		'Preference',
		'MySQL',
	];

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_get(): void {
		$user = new User($this->application);
		$name = '';
		$default = false;
		Preference::user_get($user, $name, $default);
	}

	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_user_get(): void {
		$user = new User($this->application);
		$name = null;
		$default = false;
		Preference::user_get($user, $name, $default);
	}

	/**
	 */
	public function test_user_set(): void {
		$user = new User($this->application, 1);
		Preference::userSet($user, ['Hello' => 'world']);
	}

	public function test_Preference(): void {
		$preference_class = __NAMESPACE__ . '\\' . 'Preference';

		$db = $this->application->database_registry();
		$db->queries($this->application->orm_module()->schema_synchronize($db, [
			$preference_class,
			__NAMESPACE__ . '\\' . 'Preference_Type',
		], [
			'follow' => true,
		]));

		$user = new User($this->application, 1);

		Preference::userSet($user, ['country' => 'Random']);

		$pref = new Preference($this->application, [
			'user' => $user,
			'type' => Preference_Type::registerName($this->application, 'country'),
		]);
		$result = $pref->find();
		$this->assertEquals($result, $pref);
		$this->assert_instanceof($result, $preference_class);
		$this->assertORMObject($pref);

		$result = $this->application->ormRegistry($preference_class)->queryDelete()->addWhere('user', 1)->execute();
		$this->assert_instanceof($result, __NAMESPACE__ . '\\' . 'Database_Query_Delete');
		$this->log('Deleted {n} rows from {class}', [
			'n' => $result->affectedRows(),
			'class' => $preference_class,
		]);

		$name = 'test';
		$default = 'Monkey';
		$this->assertEquals(Preference::user_get($user, $name, $default), $default);

		Preference::userSet($user, [$name => 'Ape']);
		$this->assertEquals(Preference::user_get($user, $name, $default), 'Ape');
	}

	/**
	 *
	 */
	public function test_Preference_set_parameter(): void {
		$user = new User($this->application, 1);
		Preference::userSet($user, ['Ape' => null]);
	}
}
