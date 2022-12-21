<?php
declare(strict_types=1);
/**
 * @not_test
 */

namespace zesk;

class Class_Test_SiteMonitor extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Site' => __NAMESPACE__ . '\\' . 'Test_Site',
	];

	public array $column_types = [
		'ID' => self::type_id,
		'Name' => self::type_string,
		'Site' => self::type_object,
	];
}

class Test_SiteMonitor extends ORMBase {
}

class Class_Test_Site extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Account' => __NAMESPACE__ . '\\' . 'Test_Account',
	];

	public array $column_types = [
		'ID' => self::type_id,
		'Name' => self::type_string,
		'Account' => self::type_object,
	];
}

class Test_Site extends ORMBase {
}

class Class_Test_Account extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Primary_Test_Site' => __NAMESPACE__ . '\\' . 'Test_Site',
		'Recent_Test_Site' => __NAMESPACE__ . '\\' . 'Test_Site',
	];

	public array $column_types = [
		'ID' => self::type_id,
		'Name' => self::type_string,
		'Primary_Site' => self::type_object,
		'Recent_Site' => self::type_object,
		'Cancelled' => self::type_timestamp,
	];
}

class Test_Account extends ORMBase {
}

/**
 * @see TestPerson
 */
class Class_TestPerson extends Class_Base {
	public string $id_column = 'PersonID';

	public function schema(ORMBase $object): array|string|ORM_Schema {
		return [
			'CREATE TABLE {table} ( PersonID integer unsigned NOT NULL AUTO_INCREMENT, Name varchar(64), Parent integer unsigned NULL, PRIMARY KEY (PersonID) )',
		];
	}

	public array $has_many = [
		'Favorite_Pets' => [
			'class' => __NAMESPACE__ . '\\' . 'TestPet',
			'table' => __NAMESPACE__ . '\\' . 'TestPersonPetFavorites',
			'foreign_key' => 'Person',
			'far_key' => 'Pet',
		],
		'Pets' => [
			'class' => __NAMESPACE__ . '\\' . 'TestPet',
			'link_class' => __NAMESPACE__ . '\\' . 'TestPersonPet',
			'default' => true,
			'foreign_key' => 'Person',
			'far_key' => 'Pet',
		],
		'Children' => [
			'class' => __NAMESPACE__ . '\\' . 'TestPerson',
			'foreign_key' => 'Parent',
		],
	];

	public array $column_types = [
		'PersonID' => Class_Base::type_id,
		'Name' => Class_Base::type_string,
		'Parent' => Class_Base::type_object,
	];
}

class Class_TestPersonPet extends Class_Base {
	public array $column_types = [
		'Person' => Class_Base::type_object,
		'Pet' => Class_Base::type_object,
	];

	public array $has_one = [
		'Person' => TestPerson::class,
		'Pet' => TestPet::class,
	];

	public function schema(ORMBase $object): array|string|ORM_Schema {
		return [
			'CREATE TABLE {table} ( Person integer unsigned NOT NULL, Pet integer unsigned NOT NULL, UNIQUE ppet (Person, Pet), UNIQUE pperson (Pet, Person) )',
		];
	}
}

/**
 * @property ORMBase Person
 * @property ORMBase Pet
 */
class TestPersonPet extends ORMBase {
}

/**
 * @see Class_TestPerson
 * @property ORMIterator Children
 * @property ORMIterator Pets
 * @property ORMIterator Favorite_Pets
 */
class TestPerson extends ORMBase {
}

class Class_TestPet extends Class_Base {
	public string $id_column = 'PetID';

	public array $column_types = [
		'PetID' => self::type_id,
		'Name' => self::type_string,
		'Type' => self::type_object,
	];

	public function schema(ORMBase $object): string {
		return 'CREATE TABLE {table} ( PetID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(64), `Type` integer unsigned NULL )';
	}
}

class TestPet extends ORMBase {
}

class Class_TestPersonPetFavorites extends Class_TestPersonPet {
}
class TestPersonPetFavorites extends TestPersonPet {
}
