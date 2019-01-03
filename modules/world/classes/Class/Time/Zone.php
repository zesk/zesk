<?php
namespace zesk;

class Class_Time_Zone extends Class_ORM {
	public $table = "time_zone_name";

	public $database_name = "mysql";

	public $id_column = "Time_zone_id";

	public $find_keys = array(
		"Name",
	);

	public $column_types = array(
		"Name" => self::type_string,
		"Time_zone_id" => self::type_string,
	);
}
