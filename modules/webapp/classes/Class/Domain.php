<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Domain
 * @author kent
 *
 */
class Class_Domain extends Class_ORM {
	public $codename = "WebApp_Domain";

	public array $column_types = [
		"id" => self::type_id,
		"name" => self::type_string,
		"type" => self::type_string,
		"target" => self::type_object,
		"active" => self::type_boolean,
		"accessed" => self::type_timestamp,
	];

	public array $find_keys = [
		"name",
	];

	public array $has_one = [
		"target" => "*type",
	];

	public array $column_defaults = [
		'active' => 0,
	];
}
