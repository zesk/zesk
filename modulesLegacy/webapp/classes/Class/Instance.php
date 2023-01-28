<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\WebApp;

use zesk\Server;

/**
 *
 * @author kent
 * @see Instance
 */
class Class_Instance extends Class_Base {
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
		'id' => self::TYPE_ID,
		'server' => self::TYPE_OBJECT,
		'repository' => self::TYPE_OBJECT,
		'path' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'json' => self::TYPE_JSON,
		'appversion' => self::TYPE_STRING,
		'apptype' => self::TYPE_STRING,
		'hash' => self::TYPE_HEX,
		'updated' => self::TYPE_MODIFIED,
		'serving' => self::TYPE_TIMESTAMP,
	];
}
