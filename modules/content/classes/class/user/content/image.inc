<?php
class Class_User_Content_Image extends Class_Object {
	public $id_column = false;
	public $primary_keys = array(
		"user",
		"image"
	);
	public $has_one = array(
		"user" => "User",
		"image" => "Content_Image"
	);
	public $column_types = array(
		"user" => self::type_object,
		"image" => self::type_object
	);
}