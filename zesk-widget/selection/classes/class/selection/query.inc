<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Selection_Query
 * @author kent
 */
class Class_Selection_Query extends Class_Base {
	public string $id_column = 'id';

	public array $has_one = [
		'type' => 'zesk\\Selection_Type',
	];

	public array $find_keys = [
		'type',
		'code',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'type' => self::TYPE_OBJECT,
		'code' => self::TYPE_HEX,
		'order_index' => self::TYPE_INTEGER,
		'title' => self::TYPE_TEXT,
		'query_total' => self::type_serialize,
		'query_list' => self::type_serialize,
		'created' => self::TYPE_CREATED,
		'add' => self::TYPE_BOOL,
	];
}
