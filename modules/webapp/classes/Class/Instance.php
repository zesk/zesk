<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
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
	public $codename = 'WebApp_Instance';

	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'server' => Server::class,
		'repository' => Repository::class,
	];

	/**
	 *
	 * @var array
	 */
	public array $has_many = [
		'sites' => [
			'class' => Site::class,
			'foreign_key' => 'instance',
		],
	];

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'server',
		'path',
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'server' => self::type_object,
		'repository' => self::type_object,
		'path' => self::type_string,
		'code' => self::type_string,
		'name' => self::type_string,
		'json' => self::type_json,
		'appversion' => self::type_string,
		'apptype' => self::type_string,
		'hash' => self::type_hex,
		'updated' => self::type_modified,
		'serving' => self::type_timestamp,
	];
}
