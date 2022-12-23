<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
class Schema_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/Schema_Test_Objects.php';
	}

	public function test_debug(): void {
		$value = Schema::debug();

		Schema::debug(true);
		$this->assertEquals(Schema::debug(), true);
		Schema::debug('Friday');
		$this->assertEquals(Schema::debug(), true);

		Schema::debug(false);
		$this->assertEquals(Schema::debug(), false);
		Schema::debug('Friday');
		$this->assertEquals(Schema::debug(), false);

		Schema::debug($value);
	}

	public function test_update_objects(): void {
		$object = $this->application->ormFactory(ORMUnitTest_Schema_User::class);
		Schema::update_object($object);
	}
}
