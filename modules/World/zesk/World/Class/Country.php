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
 * @see Country
 */
class Class_Country extends Class_Base {
	public string $id_column = Country::MEMBER_ID;

	public array $find_keys = [
		Country::MEMBER_CODE,
	];

	public array $column_types = [
		Country::MEMBER_ID => self::TYPE_ID,
		Country::MEMBER_CODE => self::TYPE_STRING,
		Country::MEMBER_NAME => self::TYPE_STRING,
	];
}
