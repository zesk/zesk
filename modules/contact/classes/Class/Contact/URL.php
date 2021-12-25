<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_URL extends Class_Contact_Info {
	public $contact_object_field = "URL";

	public $id_column = "id";

	public $has_one = [
		'contact' => "zesk\\Contact",
		"label" => "zesk\\Contact_Label",
	];

	public $column_types = [
		"id" => self::type_integer,
		"contact" => self::type_object,
		"label" => self::type_object,
		"hash" => self::type_integer,
		"value" => self::type_string,
	];
}
