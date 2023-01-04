<?php declare(strict_types=1);
namespace zesk\WebApp;

class Class_Cluster extends Class_Base {
	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'sitecode' => self::TYPE_STRING,
		'min_members' => self::TYPE_INTEGER,
		'max_members' => self::TYPE_INTEGER,
		'active' => self::TYPE_TIMESTAMP,
	];

	public array $find_keys = [
		'code',
	];
}
