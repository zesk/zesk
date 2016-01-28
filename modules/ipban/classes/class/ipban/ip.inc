<?php

class Class_IPBan_IP extends Class_Object {

	public $id_column = "ip";

	public $auto_column = false;

	public $columns = array(
		"ip",
		"when",
		"status"
	);
	public $column_types = array(
		"ip" => self::type_ip,
		"when" => self::type_modified,
		"status" => self::type_integer
	);

}