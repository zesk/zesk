<?php
class Class_Zesk_Content_Group extends Class_Object {

	public $column_types = array(
		"ID" => self::type_id,
		"CodeName" => self::type_string,
		"Name" => self::type_string,
		"Body" => self::type_string,
		"Created" => self::type_created,
		"Modified" => self::type_modified,
		"OrderMethod" => self::type_string,
		"ImagePath" => self::type_string
	);

	public $group_class = null;
}
