<?php
namespace zesk;

class Class_Contact_Company extends Class_Contact_Info {
	public $contact_object_field = "company";

	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string,
		"code" => self::type_string,
		"description" => self::type_string,
		"logo" => self::type_object,
		"tax_id" => self::type_string,
		"address" => self::type_object,
		"created" => self::type_created,
		"modified" => self::type_modified,
	);
}
