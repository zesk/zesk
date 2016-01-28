<?php

class Class_Contact_Date extends Class_Zesk_Contact_Date {
	public $contact_object_field = "date";

	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_id,
		"contact" => self::type_object,
		"label" => self::type_object,
		"value" => self::type_string,
		"created" => self::type_created,
		"modified" => self::type_modified
	);
}