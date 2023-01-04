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
class Class_Content_File extends Class_Base {
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
		'mime' => self::TYPE_STRING,
		'original' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'data' => self::TYPE_OBJECT,
		'description' => self::TYPE_STRING,
		'user' => self::TYPE_OBJECT,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
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
