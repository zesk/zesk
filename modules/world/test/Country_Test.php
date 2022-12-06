<?php
declare(strict_types=1);

namespace zesk;

class Country_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World', 'MySQL',
	];

	public function initialize(): void {
		$db = $this->application->database_registry();
		$this->assertNotNull($db, 'Database not connected');
		$this->requireORMTables(Country::class);
	}

	public function classes_to_test(): array {
		return [
			[Country::class, [], 'code', ], [Country::class, [], 'name', ],
		];
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_classes(string $class, array $options = [], string $id_field = ''): void {
		$this->truncateClassTables($class);
		$this->assertORMClass($class, $options, $id_field);
	}

	/**
	 * @return void
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 */
	public function test_bootstrap(): void {
		$this->truncateClassTables(Country::class);
		World_Bootstrap_Country::factory($this->application)->bootstrap();
		$this->assertGreaterThan(100, $this->application->ormFactory(Country::class)->querySelect()->addWhat('*X', 'COUNT(id)')->integer('X'));
	}

	/**
	 * @param string|null $expected_code
	 * @param string|int $mixed
	 * @return void
	 * @dataProvider data_find_country
	 * @depends      test_bootstrap
	 */
	public function test_find_country(?string $expected_code, string|int $mixed): void {
		$this->test_bootstrap();
		if ($expected_code === null) {
			$this->expectException(Exception_ORM_NotFound::class);
		}
		$country = Country::findCountry($this->application, $mixed);
		$this->assertEquals($expected_code, $country->code);
	}

	public function data_find_country(): array {
		return [
			['GB', 'gb', ], ['US', 'us', ], ['US', 'Us', ], ['US', 'US', ], ['AD', 'ad', ], [
				'DE', 'de',
			], [null, 'ay', ], [null, 'uk', ],
		];
	}
}
