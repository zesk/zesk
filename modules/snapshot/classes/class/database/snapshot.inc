<?php

class Class_Database_Snapshot extends zesk\Class_Object {

	public $id_column = "id";

	public $column_types = array(
		'id' => self::type_id,
		'name' => self::type_string,
		'dbname' => self::type_string,
		'elapsed' => self::type_double,
		'created' => self::type_string,
		'data' => self::type_string
	);

}
