<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class Currency_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World', 'ORM', 'MySQL',
	];

	public function initialize(): void {
		$this->application->ormModule()->schemaSynchronize(null, [
			Currency::class,
		], [
			'follow' => true,
		]);
	}

	public function sampleCountry(): Country {
		$result = $this->application->ormFactory(Country::class)->register([
			Country::MEMBER_CODE => 'US', Country::MEMBER_NAME => 'United States',
		]);
		assert($result instanceof Country);
		return $result;
	}

	/**
	 * @return array[]
	 */
	public function classes_to_test(): array {
		return [
			[
				Currency::class, [Currency::MEMBER_BANK_COUNTRY => $this->sampleCountry(...)], [],
			],
		];
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_classes(string $class, mixed $mixed = null, array $options = []): void {
		$mixed = $this->applyClosures($mixed);
		$this->assertORMClass($class, $mixed, $options);
	}
}
