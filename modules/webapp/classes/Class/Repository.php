<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

/**
 * @see Repository
 * @author kent
 *
 */
class Class_Repository extends Class_ORM {
	public $polymorphic = Repository::class;

	/**
	 *
	 * @var string
	 */
	public $codename = "WebApp_Repository";

	/**
	 *
	 * @var string
	 */
	public $id_column = "id";

	/**
	 *
	 * @var array
	 */
	public $find_keys = array(
		"url",
	);

	/**
	 *
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_string,
		"type" => self::type_polymorph,
		"name" => self::type_string,
		"url" => self::type_string,
		"versions" => self::type_json,
		"remote_hash" => self::type_string,
		"updated" => self::type_modified,
		"active" => self::type_boolean,
	);

	/**
	 *
	 * @var array
	 */
	public $column_defaults = array(
		"remote_hash" => "",
	);
}
