<?php
declare(strict_types=1);
/**
 * @not_test
 */

namespace zesk;

class Class_Test_SiteMonitor extends Class_ORM {
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

class Test_SiteMonitor extends ORM {
}

class Class_Test_Site extends Class_ORM {
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

class Test_Site extends ORM {
}

class Class_Test_Account extends Class_ORM {
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

class Test_Account extends ORM {
}

/**
 * @see TestPerson
 */
class Class_TestPerson extends Class_ORM {
	public string $id_column = 'PersonID';

	public function schema(ORM $object): array|string|ORM_Schema {
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
		'PersonID' => Class_ORM::type_id,
		'Name' => Class_ORM::type_string,
		'Parent' => Class_ORM::type_object,
	];
}

class Class_TestPersonPet extends Class_ORM {
	public array $column_types = [
		'Person' => Class_ORM::type_object,
		'Pet' => Class_ORM::type_object,
	];

	public array $has_one = [
		'Person' => TestPerson::class,
		'Pet' => TestPet::class,
	];

	public function schema(ORM $object): array|string|ORM_Schema {
		return [
			'CREATE TABLE {table} ( Person integer unsigned NOT NULL, Pet integer unsigned NOT NULL, UNIQUE ppet (Person, Pet), UNIQUE pperson (Pet, Person) )',
		];
	}
}

/**
 * @property ORM Person
 * @property ORM Pet
 */
class TestPersonPet extends ORM {
}

/**
 * @see Class_TestPerson
 * @property ORMIterator Children
 * @property ORMIterator Pets
 * @property ORMIterator Favorite_Pets
 */
class TestPerson extends ORM {
}

class Class_TestPet extends Class_ORM {
	public string $id_column = 'PetID';

	public array $column_types = [
		'PetID' => self::type_id,
		'Name' => self::type_string,
		'Type' => self::type_object,
	];

	public function schema(ORM $object): string {
		return 'CREATE TABLE {table} ( PetID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(64), `Type` integer unsigned NULL )';
	}
}

class TestPet extends ORM {
}

class Class_TestPersonPetFavorites extends Class_TestPersonPet {
}
class TestPersonPetFavorites extends TestPersonPet {
}
