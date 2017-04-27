<?php

namespace zesk;

class Class_Content_Video extends Class_Object {
	public $id_column = "id";
	
	public $columns = array(
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
		"modified"
	);
	public $find_keys = array(
		"name"
	);
}
