<?php declare(strict_types=1);
namespace zesk;

/**
 * Class_Content_Link
 */
class Class_Content_Link extends Class_ORM {
	public $find_keys = [
		"Hash",
		"Parent",
	];

	public $column_types = [
		'Hash' => 'hex',
		'FirstClick' => 'timestamp',
		'LastClick' => 'timestamp',
		'Created' => 'timestamp',
		'Modified' => 'timestamp',
	];

	public $has_one = [
		'Parent' => 'zesk\Content_Link',
	];
}
