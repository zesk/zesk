<?php declare(strict_types=1);
/**
 * @copyright 2013 &copy; Market Acumen, Inc.
 * @author kent
 * @see Content_Data
 */
namespace zesk;

/**
 * @see Content_Data
 * @author kent
 *
 */
class Class_Content_Data extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_ID,
		'size' => self::TYPE_INTEGER,
		'md5hash' => self::TYPE_HEX,
		'type' => self::TYPE_STRING,
		'data' => self::TYPE_SERIALIZE,
		'checked' => self::TYPE_TIMESTAMP,
		'missing' => self::TYPE_TIMESTAMP,
	];

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'md5hash',
	];
}
