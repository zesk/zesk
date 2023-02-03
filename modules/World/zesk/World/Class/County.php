<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Class_County extends Class_Base {
	public string $name = 'County';

	public string $id_column = County::MEMBER_ID;

	public string $name_column = County::MEMBER_NAME;

	public array $has_one = [
		County::MEMBER_PROVINCE => Province::class,
	];

	public array $column_types = [
		County::MEMBER_ID => self::TYPE_ID,
		County::MEMBER_PROVINCE => self::TYPE_OBJECT,
		County::MEMBER_NAME => self::TYPE_STRING,
	];
}
