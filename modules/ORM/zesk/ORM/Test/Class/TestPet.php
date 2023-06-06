<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;

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
