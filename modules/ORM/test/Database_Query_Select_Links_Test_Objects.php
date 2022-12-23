<?php
declare(strict_types=1);
/**
 * @not_test
 */

namespace zesk\ORM;

class Class_Test_SiteMonitor extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Site' => __NAMESPACE__ . '\\' . 'Test_Site',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Site' => self::TYPE_OBJECT,
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
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Account' => self::TYPE_OBJECT,
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
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Primary_Site' => self::TYPE_OBJECT,
		'Recent_Site' => self::TYPE_OBJECT,
		'Cancelled' => self::TYPE_TIMESTAMP,
	];
}

class Test_Account extends ORMBase {
}

/**
 * @see TestPerson
 */
class Class_TestPerson extends Class_Base {
	public string $id_column = 'PersonID';

	public function schema(ORMBase $object): array|string|Schema {
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
		'PersonID' => Class_Base::TYPE_ID,
		'Name' => Class_Base::TYPE_STRING,
		'Parent' => Class_Base::TYPE_OBJECT,
	];
}

class Class_TestPersonPet extends Class_Base {
	public array $column_types = [
		'Person' => Class_Base::TYPE_OBJECT,
		'Pet' => Class_Base::TYPE_OBJECT,
	];

	public array $has_one = [
		'Person' => TestPerson::class,
		'Pet' => TestPet::class,
	];

	public function schema(ORMBase $object): array|string|Schema {
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
		'PetID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Type' => self::TYPE_OBJECT,
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
