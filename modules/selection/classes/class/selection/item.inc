<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Selection_Item
 * @author kent
 *
 */
class Class_Selection_Item extends Class_ORM {
	public $id_column = false;

	public $primary_keys = [
		'type',
		'id',
	];

	public $has_one = [
		'type' => 'zesk\\Selection_Type',
		'query' => 'zesk\\Selection_Query',
	];

	public $column_types = [
		'type' => self::type_object,
		'id' => self::type_object,
		'add' => self::type_boolean,
		'query' => self::type_object,
	];
}
