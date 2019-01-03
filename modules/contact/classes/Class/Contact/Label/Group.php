<?php
namespace zesk;

class Class_Contact_Label_Group extends Class_ORM {
	protected $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string,
	);

	protected $find_keys = array(
		"name",
	);
}
