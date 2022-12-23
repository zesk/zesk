<?php declare(strict_types=1);

namespace zesk\ORM;

class Class_TestORM extends Class_Base {
	public string $id_column = 'ID';

	public array $find_keys = [
		'Name',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Price' => self::TYPE_FLOAT,
		'Foo' => self::TYPE_INTEGER,
	];

	public function schema(ORMBase $object): string|array|ORM_Schema {
		$table = $this->table;
		return [
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Price decimal(12,2), Foo integer NULL )",
		];
	}
}
class TestORM extends ORMBase {
}

class Class_TestORMTag extends Class_Base {
	public string $id_column = 'ID';

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Parent' => self::TYPE_OBJECT,
	];

	public array $has_one = [
		'Parent' => TestORM::class,
	];

	public function schema(ORMBase $object): string|array|ORM_Schema {
		$table = $this->table;
		return [
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Parent integer NOT NULL )",
		];
	}
}

class TestORMProductBase extends ORMBase {
}
