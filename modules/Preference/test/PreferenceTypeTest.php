<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Preference;

use zesk\ORM\ORMUnitTest;

/**
 *
 * @author kent
 *
 */
class PreferenceTypeTest extends ORMUnitTest {
	protected array $load_modules = [
		'Preference',
	];

	protected function initialize(): void {
		$this->schemaSynchronize(Type::class);
	}

	public function test_ORMClass(): void {
		$this->assertORMClass(Type::class, null, [], Type::MEMBER_CODE);
	}

	public function test_object(): void {
		$x = $this->application->modelFactory(Type::class);
		$this->assertInstanceOf(Type::class, $x);

		$code_name = 'Poore';
		$type = Type::registerName($this->application, $code_name);
		$this->assertInstanceOf(Type::class, $type);
		$this->assertEquals($code_name, $type->name());
		$this->assertNotEmpty($type->id());
	}
}
