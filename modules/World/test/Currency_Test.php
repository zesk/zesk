<?php declare(strict_types=1);
namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class Currency_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World',
		'ORM',
		'MySQL',
	];

	public function initialize(): void {
		$this->application->orm_module()->schema_synchronize(null, [
			Currency::class,
		], [
			'follow' => true,
		]);
	}

	/**
	 * @return array[]
	 */
	public function classes_to_test(): array {
		return [
			[
				Currency::class,
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
		$this->assertORMClass($class, $options);
	}
}
