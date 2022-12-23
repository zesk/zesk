<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

/**
 * @see Language
 * @author kent
 *
 */
class Class_Language extends Class_Base {
	public string $id_column = 'id';

	public $name_column = 'name';

	public string $name = 'Language';

	public array $find_keys = [
		'code',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_STRING,
		'dialect' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
	];

	/**
	 * @todo Make country ID two-letter code
	 * @var array
	 */
	// 	public array $has_one = array()
	// 		'dialect' => 'Country'
	// 	;
}
