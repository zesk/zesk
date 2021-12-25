<?php declare(strict_types=1);
namespace zesk;

class World_Test extends Test_ORM {
	protected array $load_modules = [
		"World",
		"MySQL",
	];

	public function initialize(): void {
		$db = $this->application->database_registry();
		$this->assert_not_null($db, "Database not connected");
		$this->require_tables(__NAMESPACE__ . "\\" . "Country");
	}

	public function classes_to_test() {
		return [
			[
				City::class,
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
			[
				Currency::class,
				[],
			],
		];
	}
}
