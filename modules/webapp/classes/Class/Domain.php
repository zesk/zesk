<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

class Class_Domain extends Class_ORM {
	public $codename = "WebApp_Domain";

	public $column_types = array(
		"id" => self::type_id,
		"name" => self::type_string,
		"type" => self::type_string,
		"target" => self::type_object,
		"active" => self::type_timestamp,
	);

	public $has_one = array(
		"target" => "*type",
	);
}
