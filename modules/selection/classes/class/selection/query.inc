<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Selection_Query
 * @author kent
 */
class Class_Selection_Query extends Class_ORM {
	public string $id_column = 'id';

	public array $has_one = [
		'type' => 'zesk\\Selection_Type',
	];

	public array $find_keys = [
		'type',
		'code',
	];

	public array $column_types = [
		'id' => self::type_id,
		'type' => self::type_object,
		'code' => self::type_hex,
		'order_index' => self::type_integer,
		'title' => self::type_text,
		'query_total' => self::type_serialize,
		'query_list' => self::type_serialize,
		'created' => self::type_created,
		'add' => self::type_boolean,
	];
}
