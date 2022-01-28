<?php declare(strict_types=1);
namespace zesk;

class Class_TestORM extends Class_ORM {
	public string $id_column = "ID";

	public array $column_types = [
		"ID" => self::type_id,
		'Name' => self::type_string,
		'Price' => self::type_double,
		'Foo' => self::type_integer,
	];
}
class TestORM extends ORM {
	public function schema(): string|array|ORM_Schema|null {
		$table = $this->table;
		return [
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Price decimal(12,2), Foo integer NULL )",
		];
	}

	public function specification() {
		return [
			"table" => get_class($this),
			"fields" => "Foo",
			"find_keys" => [
				"Foo",
			],
		];
	}
}
