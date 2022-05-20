<?php declare(strict_types=1);
namespace zesk;

class Class_Time_Zone extends Class_ORM {
	public string $table = 'time_zone_name';

	public $database_name = 'mysql';

	public string $id_column = 'Time_zone_id';

	public array $find_keys = [
		'Name',
	];

	public array $column_types = [
		'Name' => self::type_string,
		'Time_zone_id' => self::type_string,
	];
}
