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
class Preference_Type_Test extends Test_Unit {
	protected array $load_modules = [
		'Preference',
		'MySQL',
	];

	protected function initialize(): void {
		$this->schema_synchronize(Preference_Type::class);
	}

	public function test_object(): void {
		$x = $this->application->modelFactory(Preference_Type::class);
		$this->assertInstanceOf(Preference_Type::class, $x);

		$code_name = 'Poore';
		$type = Preference_Type::registerName($this->application, $code_name);
		$this->assertInstanceOf(Preference_Type::class, $type);
		$this->assertEquals($code_name, $type->name());
		$this->assertNotEmpty($type->id());
	}
}
