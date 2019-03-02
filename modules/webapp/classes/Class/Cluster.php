<?php
namespace zesk\WebApp;

class Class_Cluster extends Class_ORM {
	public $id_column = "id";
	public $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string,
		"code" => self::type_string,
		"hostcode" => self::type_string,
		"min_members" => self::type_string,
		"max_members" => self::type_object,
		"active" => self::type_timestamp
	);
	public $find_keys = array(
		"code"
	);
}