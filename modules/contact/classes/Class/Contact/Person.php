<?php
namespace zesk;

class Class_Contact_Person extends Class_Contact_Info {
	public $contact_object_field = "Person";

	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_id,
		"contact" => self::type_object,
		"label" => self::type_object,
		"name_prefix" => self::type_string,
		"name_first" => self::type_string,
		"name_middle" => self::type_string,
		"name_last" => self::type_string,
		"name_suffix" => self::type_string,
		"name_nick" => self::type_string,
		"name_maiden" => self::type_string,
		"title" => self::type_string,
		"company" => self::type_object,
		"gender" => self::type_integer,
		"spouse" => self::type_string,
		"children" => self::type_string,
		"created" => self::type_created,
		"modified" => self::type_modified,
	);
}
