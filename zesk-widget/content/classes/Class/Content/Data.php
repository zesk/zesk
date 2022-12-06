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
class Class_Content_Data extends Class_ORM {
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
		'id' => self::type_id,
		'size' => self::type_integer,
		'md5hash' => self::type_hex,
		'type' => self::type_string,
		'data' => self::type_serialize,
		'checked' => self::type_timestamp,
		'missing' => self::type_timestamp,
	];

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'md5hash',
	];
}
