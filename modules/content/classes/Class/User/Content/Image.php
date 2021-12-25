<?php declare(strict_types=1);
namespace zesk;

class Class_User_Content_Image extends Class_ORM {
	public $id_column = false;

	public $primary_keys = [
		"user",
		"image",
	];

	public $has_one = [
		"user" => "zesk\User",
		"image" => "zesk\Content_Image",
	];

	public $column_types = [
		"user" => self::type_object,
		"image" => self::type_object,
	];
}
