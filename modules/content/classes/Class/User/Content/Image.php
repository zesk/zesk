<?php
namespace zesk;

class Class_User_Content_Image extends Class_ORM {
	public $id_column = false;

	public $primary_keys = array(
		"user",
		"image",
	);

	public $has_one = array(
		"user" => "zesk\User",
		"image" => "zesk\Content_Image",
	);

	public $column_types = array(
		"user" => self::type_object,
		"image" => self::type_object,
	);
}
