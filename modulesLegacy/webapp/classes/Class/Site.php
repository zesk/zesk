<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Site
 * @author kent
 *
 */
class Class_Site extends Class_Base {
	public $codename = 'WebApp_Site';

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'instance',
		'code',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'instance' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'priority' => self::TYPE_INTEGER,
		'path' => self::TYPE_STRING,
		'data' => self::TYPE_JSON,
		'errors' => self::TYPE_JSON,
		'valid' => self::TYPE_BOOL,
	];

	public array $has_one = [
		'instance' => Instance::class,
	];
}
