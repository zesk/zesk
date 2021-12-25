<?php declare(strict_types=1);
namespace zesk;

class Class_Content_Video extends Class_ORM {
	public $id_column = "id";

	public $columns = [
		"id",
		"name",
		"parent",
		"hash",
		"width",
		"height",
		"url",
		"filepath",
		"description",
		"created",
		"modified",
	];

	public $find_keys = [
		"name",
	];
}
