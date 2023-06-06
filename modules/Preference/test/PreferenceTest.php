<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Preference;

use zesk\Exception\ParameterException;
use zesk\ORM\ORMNotFound;
use zesk\ORM\ORMUnitTest;
use zesk\ORM\User;

/**
 *
 * @author kent
 *
 */
class PreferenceTest extends ORMUnitTest {
	protected array $load_modules = [
		'Preference',
	];

	protected function initialize(): void {
		$this->schemaSynchronize(Value::class);
	}

	/**
	 * @return User
	 */
	protected function emptyUser(): User {
		$user = $this->application->ormFactory(User::class);
		$this->assertInstanceOf(User::class, $user);
		return $user;
	}

	protected function validUser(): User {
		$this->requireORMTables(User::class);
		$user = $this->application->ormFactory(User::class);
		$this->assertInstanceOf(User::class, $user);
		$members = [
			$user->column_email() => 'preference-test@example.com', 'name_first' => 'Preference', 'name_last' => 'Test',
			'is_active' => true,
		];
		return $user->setMembers($members)->register();
	}

	public function test_ORMClass(): void {
		$this->truncateClassTables(Value::class);
		$this->assertORMClass(Value::class, [
			'user' => $this->validUser(), 'type' => Type::registerName($this->application, $this->randomHex(8)),
		]);
	}

	/**
	 *
	 */
	public function test_get_blank(): void {
		$this->expectException(ParameterException::class);
		$user = $this->emptyUser();
		Value::userGet($user, '');
	}

	/**
	 */
	public function test_user_set(): void {
		$user = $this->validUser();
		$value = $this->randomHex($this->randomInteger(32, 64));
		$theKey = $this->randomHex(32);
		$result = Value::userSet($user, [$theKey => $value]);
		$this->assertCount(1, $result);
		$this->assertArrayHasKey($theKey, $result);
		$this->assertIsInt($result[$theKey]);
		$this->assertEquals($value, Value::userGet($user, $theKey));
	}

	public function test_Preference(): void {
		$user = $this->validUser();

		$preference_class = Value::class;

		Value::userSet($user, ['country' => 'Random']);

		$pref = new Value($this->application, [
			'user' => $user, 'type' => Type::registerName($this->application, 'country'),
		]);
		$result = $pref->find();
		$this->assertEquals($result, $pref);
		$this->assertInstanceOf(Value::class, $result);

		$result = $this->application->ormRegistry($preference_class)->queryDelete()->addWhere('user', $user->id())->execute();
		$this->log('Deleted {n} rows from {class}', [
			'n' => $result->affectedRows(), 'class' => $preference_class,
		]);
		$this->assertORMObject($pref);
	}

	public function test_missing(): void {
		$user = $this->validUser();
		$name = '-missing-';
		$this->assertFalse(Value::userHas($user, $name));

		$this->expectException(ORMNotFound::class);
		Value::userGet($user, $name);
	}
}
