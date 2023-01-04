<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk\WebApp;

use Git\classes\Repository;

/**
 * @see Repository
 * @author kent
 *
 */
class Class_Repository extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public $polymorphic = Repository::class;

	/**
	 *
	 * @var string
	 */
	public $codename = 'WebApp_Repository';

	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'url',
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'type' => self::TYPE_POLYMORPH,
		'name' => self::TYPE_STRING,
		'url' => self::TYPE_STRING,
		'versions' => self::TYPE_JSON,
		'remote_hash' => self::TYPE_STRING,
		'updated' => self::TYPE_MODIFIED,
		'active' => self::TYPE_BOOL,
	];

	/**
	 *
	 * @var array
	 */
	public array $column_defaults = [
		'remote_hash' => '',
	];
}
