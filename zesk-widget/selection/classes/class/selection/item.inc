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
class Class_Selection_Item extends Class_Base {
	public array $primary_keys = [
		'type',
		'id',
	];

	public array $has_one = [
		'type' => 'zesk\\Selection_Type',
		'query' => 'zesk\\Selection_Query',
	];

	public array $column_types = [
		'type' => self::TYPE_OBJECT,
		'id' => self::TYPE_OBJECT,
		'add' => self::TYPE_BOOL,
		'query' => self::TYPE_OBJECT,
	];
}
