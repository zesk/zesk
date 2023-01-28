<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\ORM\ORMUnitTest;

class World_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World', 'MySQL',
	];

	public function sampleProvince(): Province {
		$province = $this->application->ormFactory(Province::class, [
			Province::MEMBER_NAME => 'Place ' . ($randomHex = $this->randomHex(6)),
			Province::MEMBER_CODE => $this->randomHex(2),
			Province::MEMBER_COUNTRY => Country::findCountry($this->application, 'US'),
		])->register();
		assert($province instanceof Province);
		return $province;
	}

	public function classes_to_test(): array {
		$this->setUp();
		return [
			[
				City::class, [City::MEMBER_PROVINCE => $this->sampleProvince()], [],
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
		$this->truncateClassTables($class);
		$this->assertORMClass($class, $mixed, $options);
	}
}
