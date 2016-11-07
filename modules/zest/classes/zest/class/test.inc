<?php

namespace zest;

class Class_Test extends \Class_Object {
	public $id_column = "id";
	public $has_one = array(
		'project' => "zest\\Project"
	);
	public $find_keys = array(
		"project",
		"path"
	);
	public $column_types = array(
		'id' => self::type_id,
		'project' => self::type_object,
		'name' => self::type_string,
		'path' => self::type_string,
		'class' => self::type_string,
		'created' => self::type_created,
		'last_run' => self::type_timestamp,
		'last_status' => self::type_boolean
	);
}
