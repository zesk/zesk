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
 * Class_Currency
 */
class Class_Currency extends Class_Base {
	public string $id_column = Currency::MEMBER_ID;

	public array $find_keys = [
		Currency::MEMBER_BANK_COUNTRY,
		Currency::MEMBER_CODE,
	];

	public string $name_column = Currency::MEMBER_NAME;

	public string $name = 'Currency';

	public array $has_one = [
		Currency::MEMBER_BANK_COUNTRY => Country::class,
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		Currency::MEMBER_BANK_COUNTRY => self::TYPE_OBJECT,
		Currency::MEMBER_NAME => self::TYPE_STRING,
		Currency::MEMBER_CODE => self::TYPE_STRING,
		Currency::MEMBER_SYMBOL => self::TYPE_STRING,
		Currency::MEMBER_FRACTIONAL => self::TYPE_INTEGER,
		Currency::MEMBER_FRACTIONAL_UNITS => self::TYPE_STRING,
		Currency::MEMBER_FORMAT => self::TYPE_STRING,
		Currency::MEMBER_PRECISION => self::TYPE_INTEGER,
	];

	public array $column_defaults = [
		Currency::MEMBER_PRECISION => 2,
	];
}
