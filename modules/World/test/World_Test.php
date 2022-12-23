<?php declare(strict_types=1);
namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class World_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World',
		'MySQL',
	];

	public function classes_to_test(): array {
		return [
			[
				City::class,
				[],
			],
			[
				County::class,
				[],
			],
			[
				Country::class,
				[],
			],
			[
				Province::class,
				[],
			],
		];
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_classes(string $class, array $options = []): void {
		$this->truncateClassTables($class);
		$this->assertORMClass($class, $options);
	}
}
