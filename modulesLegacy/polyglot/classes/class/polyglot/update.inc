<?php declare(strict_types=1);
namespace zesk;

/**
 * @see PolyGlot_Update
 * @author kent
 *
 */
class Class_PolyGlot_Update extends Class_Base {
	public string $id_column = 'locale';

	public $auto_column = false;

	public array $column_types = [
		'locale' => self::TYPE_STRING,
		'updated' => self::TYPE_TIMESTAMP,
		'user' => self::TYPE_OBJECT,
	];

	protected $database_group = 'zesk\\PolyGlot_Token';
}
