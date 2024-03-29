<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class Class_TestORM extends Class_Base {
	public string $id_column = 'ID';

	public array $find_keys = [
		'Name',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Price' => self::TYPE_DOUBLE,
		'Foo' => self::TYPE_INTEGER,
		'Data' => self::TYPE_SERIALIZE,
	];

	public function schema(ORMBase $object): string|array|Schema {
		$table = $this->table;
		return [
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Price decimal(12,2), Foo integer NULL, Data blob NULL )",
		];
	}
}
