<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

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
