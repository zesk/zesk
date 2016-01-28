<?php

class Class_Time_Zone extends Class_Object {
	public $table = "time_zone_name";

	public $database_name = "mysql";

	public $id_column = "Time_zone_id";

	public $find_keys = array(
		"Name"
	);
	public $columns = array(
		"Name",
		"Time_zone_id"
	);
}
