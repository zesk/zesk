<?php
/**
 * Class_Zesk_Contact_Label
 */
class Class_Zesk_Contact_Label extends Class_Object {

	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_integer,
		"account" => self::type_object,
		"group" => self::type_object,
		"type" => self::type_object,
		"code" => self::type_string,
		"name" => self::type_string,
		"created" => self::type_created,
		"modified" => self::type_modified
	);

	public $find_keys = array(
		"account",
		"code"
	);
}

