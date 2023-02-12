<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class Currency_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World', 'ORM', 'MySQL',
	];

	public function initialize(): void {
		$this->schemaSynchronize([
			Currency::class,
		], [
			'follow' => true,
		]);
	}

	public static function sampleCountry(): Country {
		$app = self::app();
		$result = $app->ormFactory(Country::class, [
			Country::MEMBER_CODE => 'US', Country::MEMBER_NAME => 'United States',
		])->register();
		assert($result instanceof Country);
		return $result;
	}

	/**
	 * @return array[]
	 */
	public static function classes_to_test(): array {
		return [
			[
				Currency::class, [Currency::MEMBER_BANK_COUNTRY => fn () => self::sampleCountry()], [],
			],
		];
	}

	/**
	 *
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_classes(string $class, mixed $mixed = null, array $options = []): void {
		$mixed = $this->applyClosures($mixed);
		$this->assertORMClass($class, $mixed, $options);
	}
}
