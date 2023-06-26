<?php
declare(strict_types=1);

namespace zesk\World;

use zesk\Database\Exception\SQLException;
use zesk\Exception\KeyNotFound;
use zesk\ORM\ORMUnitTest;
use zesk\ORM\ORMNotFound;

class Country_Test extends ORMUnitTest {
	protected array $load_modules = [
		'World',
	];

	public function initialize(): void {
		$db = $this->application->databaseRegistry();
		$this->assertNotNull($db, 'Database not connected');
		$this->requireORMTables(Country::class);
	}

	public static function classes_to_test(): array {
		return [
			[Country::class, null, [], 'code', ],
			[Country::class, null, [], 'name', ],
		];
	}

	/**
	 *
	 * @param string $class
	 * @param array $options
	 * @dataProvider classes_to_test
	 */
	public function test_classes(string $class, mixed $mixed = null, array $options = [], string $id_field = ''): void {
		$this->truncateClassTables($class);
		$this->assertORMClass($class, $mixed, $options, $id_field);
	}

	/**
	 * @return void
	 * @throws Database\Exception\SQLException
	 * @throws KeyNotFound
	 */
	public function test_bootstrap(): void {
		$this->truncateClassTables(Country::class);
		Bootstrap_Country::factory($this->application)->bootstrap();
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
			$this->expectException(ORMNotFound::class);
		}
		$country = Country::findCountry($this->application, $mixed);
		$this->assertEquals($expected_code, $country->code);
	}

	public static function data_find_country(): array {
		return [
			['GB', 'gb', ], ['US', 'us', ], ['US', 'Us', ], ['US', 'US', ], ['AD', 'ad', ], [
				'DE', 'de',
			], [null, 'ay', ], [null, 'uk', ],
		];
	}
}
