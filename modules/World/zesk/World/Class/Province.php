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
 * @see Province
 * @see City
 * @see Country
 */
class Class_Province extends Class_Base {
	public string $id_column = Province::MEMBER_ID;

	public string $name = 'Province:=State';

	public array $column_types = [
		Province::MEMBER_ID => self::TYPE_ID,
		Province::MEMBER_COUNTRY => self::TYPE_OBJECT,
		Province::MEMBER_CODE => self::TYPE_STRING,
		Province::MEMBER_NAME => self::TYPE_STRING,
	];

	public array $find_keys = [
		Province::MEMBER_COUNTRY,
		Province::MEMBER_NAME,
	];

	public array $has_one = [
		Province::MEMBER_COUNTRY => Country::class,
	];
}
