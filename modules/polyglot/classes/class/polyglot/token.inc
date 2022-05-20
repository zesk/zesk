<?php declare(strict_types=1);
namespace zesk;

/**
 * @see PolyGlot_Token
 * @author kent
 *
 */
class Class_PolyGlot_Token extends Class_ORM {
	public string $id_column = 'id';

	public array $has_one = [
		'user' => 'zesk\\User',
	];

	public array $column_types = [
		'id' => self::type_id,
		'language' => self::type_string,
		'dialect' => self::type_string,
		'md5' => self::type_hex,
		'original' => self::type_string,
		'translation' => self::type_string,
		'user' => self::type_object,
		'context' => self::type_string,
		'status' => self::type_string,
		'updated' => self::type_modified,
	];
}
