<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

/**
 * @see Language
 * @author kent
 *
 */
class Class_Language extends Class_Base {
	public string $id_column = Language::MEMBER_ID;

	public string $name_column = Language::MEMBER_NAME;

	public string $name = 'Language';

	public array $find_keys = [
		Language::MEMBER_CODE,
	];

	public array $column_types = [
		Language::MEMBER_ID => self::TYPE_ID,
		Language::MEMBER_CODE => self::TYPE_STRING,
		Language::MEMBER_DIALECT => self::TYPE_STRING,
		Language::MEMBER_NAME => self::TYPE_STRING,
	];

	/**
	 * @todo Make country ID two-letter code
	 */
	// 	public array $has_one = array()
	// 		'dialect' => 'Country'
	// 	;
}
