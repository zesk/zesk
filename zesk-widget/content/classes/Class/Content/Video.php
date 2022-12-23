<?php declare(strict_types=1);
namespace zesk;

class Class_Content_Video extends Class_Base {
	public string $id_column = 'id';

	public $columns = [
		'id',
		'name',
		'parent',
		'hash',
		'width',
		'height',
		'url',
		'filepath',
		'description',
		'created',
		'modified',
	];

	public array $find_keys = [
		'name',
	];
}
