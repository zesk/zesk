<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

/**
 * @see Domain
 * @author kent
 *
 */
class Class_Domain extends Class_Base {
	public $codename = 'WebApp_Domain';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'target' => self::TYPE_OBJECT,
		'active' => self::TYPE_BOOL,
		'accessed' => self::TYPE_TIMESTAMP,
	];

	public array $find_keys = [
		'name',
	];

	public array $has_one = [
		'target' => '*type',
	];

	public array $column_defaults = [
		'active' => 0,
	];
}
