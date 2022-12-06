<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 * @see Content_File
 */
class Class_Content_File extends Class_ORM {
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
		'mime' => self::type_string,
		'original' => self::type_string,
		'name' => self::type_string,
		'data' => self::type_object,
		'description' => self::type_string,
		'user' => self::type_object,
		'created' => self::type_created,
		'modified' => self::type_modified,
	];

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'data' => 'zesk\\Content_Data',
		'user' => 'zesk\\User',
	];
}
