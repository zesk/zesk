<?php
namespace zesk;

class Class_County extends Class_ORM {
	public $name = "County";

	public $id_column = "id";

	public $name_column = "name";

	public $has_one = array(
		'province' => "zesk\\Province",
	);

	public $column_types = array(
		'id' => self::type_id,
		'province' => self::type_object,
		'name' => self::type_string,
	);
}
