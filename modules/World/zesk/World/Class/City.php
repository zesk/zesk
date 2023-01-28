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
 * @see City
 */
class Class_City extends Class_Base {
	public string $id_column = City::MEMBER_ID;

	public array $find_keys = [
		City::MEMBER_NAME,
	];

	public array $has_one = [
		City::MEMBER_PROVINCE => Province::class,
		City::MEMBER_COUNTY => County::class,
	];

	public array $column_types = [
		City::MEMBER_ID => self::TYPE_ID,
		City::MEMBER_PROVINCE => self::TYPE_OBJECT,
		City::MEMBER_COUNTY => self::TYPE_OBJECT,
		City::MEMBER_NAME => self::TYPE_STRING,
	];
}
