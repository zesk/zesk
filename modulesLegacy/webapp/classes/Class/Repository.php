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
		'id' => self::type_id,
		'code' => self::type_string,
		'type' => self::type_polymorph,
		'name' => self::type_string,
		'url' => self::type_string,
		'versions' => self::type_json,
		'remote_hash' => self::type_string,
		'updated' => self::type_modified,
		'active' => self::type_boolean,
	];

	/**
	 *
	 * @var array
	 */
	public array $column_defaults = [
		'remote_hash' => '',
	];
}
