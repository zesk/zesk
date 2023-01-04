<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Selection_Type
 * @author kent
 *
 */
class Class_Selection_Type extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'code',
		'user',
	];

	public array $has_one = [
		'user' => 'zesk\\User',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'code' => self::TYPE_TEXT,
		'class' => self::TYPE_TEXT,
		'user' => self::TYPE_OBJECT,
		'created' => self::TYPE_CREATED,
	];

	public array $has_many = [
		'items' => [
			'class' => 'zesk\\Selection_Item',
			'foreign_key' => 'type',
		],
		'queries' => [
			'class' => 'zesk\\Selection_Query',
			'foreign_key' => 'type',
			'order_by' => 'order_index',
		],
	];
}
