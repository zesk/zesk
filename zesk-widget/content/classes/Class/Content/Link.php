<?php declare(strict_types=1);
namespace zesk;

/**
 * Class_Content_Link
 */
class Class_Content_Link extends Class_Base {
	public array $find_keys = [
		'Hash',
		'Parent',
	];

	public array $column_types = [
		'Hash' => 'hex',
		'FirstClick' => 'timestamp',
		'LastClick' => 'timestamp',
		'Created' => 'timestamp',
		'Modified' => 'timestamp',
	];

	public array $has_one = [
		'Parent' => 'zesk\Content_Link',
	];
}
