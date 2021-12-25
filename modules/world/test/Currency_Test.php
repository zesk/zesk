<?php declare(strict_types=1);
namespace zesk;

class Currency_Test extends Test_ORM {
	protected array $load_modules = [
		"World",
		"ORM",
		"MySQL",
	];

	public function initialize(): void {
		$this->application->orm_module()->schema_synchronize(null, [
			Currency::class,
		], [
			"follow" => true,
		]);
		parent::initialize();
	}

	public function classes_to_test() {
		return [
			[
				Currency::class,
				[],
			],
		];
	}

	/**
	 *
	 * @param unknown $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_currency($class, array $options = []): void {
		$this->run_test_class($class, $options);
	}
}
