<?php declare(strict_types=1);
namespace zesk;

class Class_Database_Snapshot extends Class_Base {
	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'dbname' => self::TYPE_STRING,
		'elapsed' => self::type_double,
		'created' => self::TYPE_STRING,
		'data' => self::TYPE_STRING,
	];
}
