<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class World_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World', 'MySQL',
	];

	public static function sampleCountry(): Country {
		$result = self::app()->ormFactory(Country::class)->register([
			Country::MEMBER_CODE => 'US', Country::MEMBER_NAME => 'United States',
		]);
		assert($result instanceof Country);
		return $result;
	}

	public static function sampleProvince(): Province {
		$province = self::app()->ormFactory(Province::class, [
			Province::MEMBER_NAME => 'Place ' . self::randomHex(6),
			Province::MEMBER_CODE => self::randomHex(2),
			Province::MEMBER_COUNTRY => self::sampleCountry(),
		])->register();
		assert($province instanceof Province);
		return $province;
	}

	/**
	 * @return array
	 */
	public static function classes_to_test(): array {
		return [
			[
				City::class, [City::MEMBER_PROVINCE => self::sampleProvince(...)], [],
			], [
				County::class, [], [],
			], [
				Country::class, [], [],
			], [
				Province::class, [], [],
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
		$options = $this->applyClosures($options);
		$this->schemaSynchronize($class);
		$this->truncateClassTables($class);
		$this->assertORMClass($class, $mixed, $options);
	}
}
