<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class ORM_Schema_Test extends Test_Unit {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/ORM_Schema_Test_Objects.php';
		parent::initialize();
	}

	public function test_debug(): void {
		$value = ORM_Schema::debug();

		ORM_Schema::debug(true);
		$this->assert_equal(ORM_Schema::debug(), true);
		ORM_Schema::debug('Friday');
		$this->assert_equal(ORM_Schema::debug(), true);

		ORM_Schema::debug(false);
		$this->assert_equal(ORM_Schema::debug(), false);
		ORM_Schema::debug('Friday');
		$this->assert_equal(ORM_Schema::debug(), false);

		ORM_Schema::debug($value);
	}

	public function test_update_objects(): void {
		$object = $this->application->orm_factory(Test_ORM_Schema_User::class);
		ORM_Schema::update_object($object);
	}
}
