<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;
use zesk\ORM\TestPerson;
use zesk\ORM\TestPet;

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
