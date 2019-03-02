<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

class Class_Host extends Class_ORM {
	public $codename = "WebApp_Host";
	public $column_types = array(
		"id" => self::type_id,
		"instance" => self::type_object,
		"name" => self::type_string,
		"code" => self::type_string,
		"priority" => self::type_integer,
		"path" => self::type_string,
		"data" => self::type_json,
		"errors" => self::type_json,
		"valid" => self::type_boolean
	);
	public $has_one = array(
		"instance" => Instance::class
	);
}