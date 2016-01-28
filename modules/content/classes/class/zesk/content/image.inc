<?php
class Class_Zesk_Content_Image extends Class_Object {
	/**
	 *
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 *
	 * @var string
	 */
	public $name = "Image";
	
	/**
	 *
	 * @var string
	 */
	public $name_column = "title";
	
	/**
	 *
	 * @var array
	 */
	public $find_keys = array(
		"data",
		"path"
	);
	
	/**
	 *
	 * @var array
	 */
	public $has_one = array(
		"data" => "Content_Data"
	);
	public $has_many = array(
		"users" => array(
			"class" => "User",
			"table" => "User_Content_Image",
			"foreign_key" => "image",
			"far_key" => "user"
		)
	);
	public $column_types = array(
		"id" => self::type_id,
		"data" => self::type_object,
		"width" => self::type_integer,
		"height" => self::type_integer,
		"mime_type" => self::type_string,
		"path" => self::type_string,
		"title" => self::type_string,
		"description" => self::type_string,
		"created" => self::type_created,
		"modified" => self::type_modified
	);
	public $column_defaults = array(
		'title' => '',
		"description" => ""
	);
}

