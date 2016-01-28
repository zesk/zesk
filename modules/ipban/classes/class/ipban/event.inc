<?php
class Class_IPBan_Event extends Class_Object {

	public $columns = array(
		'ip',
		'utc',
		'tag'
	);

	public $column_types = array(
		'ip' => self::type_ip,
		'utc' => self::type_timestamp,
		'tag' => self::type_object
	);

	public $has_one = array(
		'tag' => 'IPBan_Tag'
	);

	public $primary_keys = array(
		'ip',
		'utc',
		'tag'
	);

	public $find_keys = array(
		'ip',
		'utc',
		'tag'
	);

	protected $database_group = "IPBan";
}
