<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_Content_Group extends Class_Base {
	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'body' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
		'order_by' => self::TYPE_STRING,
	];

	/**
	 *
	 * @var string
	 */
	public $group_class = null;
}
