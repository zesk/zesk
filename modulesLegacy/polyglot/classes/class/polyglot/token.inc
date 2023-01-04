<?php declare(strict_types=1);
namespace zesk;

/**
 * @see PolyGlot_Token
 * @author kent
 *
 */
class Class_PolyGlot_Token extends Class_Base {
	public string $id_column = 'id';

	public array $has_one = [
		'user' => 'zesk\\User',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'language' => self::TYPE_STRING,
		'dialect' => self::TYPE_STRING,
		'md5' => self::TYPE_HEX,
		'original' => self::TYPE_STRING,
		'translation' => self::TYPE_STRING,
		'user' => self::TYPE_OBJECT,
		'context' => self::TYPE_STRING,
		'status' => self::TYPE_STRING,
		'updated' => self::type_modified,
	];
}
