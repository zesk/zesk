<?php declare(strict_types=1);
namespace zesk;

/**
 * @see PolyGlot_Update
 * @author kent
 *
 */
class Class_PolyGlot_Update extends Class_ORM {
	public string $id_column = "locale";

	public $auto_column = false;

	public array $column_types = [
		"locale" => self::type_string,
		"updated" => self::type_timestamp,
		"user" => self::type_object,
	];

	protected $database_group = "zesk\\PolyGlot_Token";
}
