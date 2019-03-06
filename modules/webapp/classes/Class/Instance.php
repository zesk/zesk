<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Server;

/**
 *
 * @author kent
 * @see Instance
 */
class Class_Instance extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public $codename = "WebApp_Instance";

	/**
	 *
	 * @var string
	 */
	public $id_column = "id";

	/**
	 *
	 * @var array
	 */
	public $has_one = array(
		"server" => Server::class,
		"repository" => Repository::class
	);

	/**
	 *
	 * @var array
	 */
	public $has_many = array(
		"sites" => array(
			"class" => Site::class,
			"foreign_key" => "instance"
		)
	);

	/**
	 *
	 * @var array
	 */
	public $find_keys = array(
		"server",
		"path"
	);

	/**
	 *
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"server" => self::type_object,
		"repository" => self::type_object,
		"path" => self::type_string,
		"code" => self::type_string,
		"name" => self::type_string,
		"appversion" => self::type_string,
		"apptype" => self::type_string,
		"hash" => self::type_hex,
		"updated" => self::type_modified,
		"serving" => self::type_timestamp
	);
}
