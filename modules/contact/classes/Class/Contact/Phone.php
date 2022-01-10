<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Phone extends Class_Contact_Info {
	public $contact_object_field = "Phone";

	public string $id_column = "id";

	public array $column_types = [
		"id" => self::type_id,
		"contact" => self::type_object,
		"label" => self::type_object,
		"value" => self::type_string,
		"created" => self::type_created,
		"modified" => self::type_modified,
	];
}
