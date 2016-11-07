<?php

namespace zest;

/**
 * @see Project
 * @author kent
 *
 */
class Class_Project extends \Class_Object {
	public $id_column = "id";
	public $find_keys = array(
		"path"
	);
	public $column_types = array(
		'id' => self::type_id,
		'path' => self::type_string,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'tests_updated' => self::type_datetime,
		'executed' => self::type_datetime,
		'first_success' => self::type_datetime,
		'first_failure' => self::type_datetime,
		'last_success' => self::type_datetime,
		'last_failure' => self::type_datetime,
		'stats_total' => self::type_integer,
		'stats_success' => self::type_integer,
		'stats_failure' => self::type_integer,
		'status' => self::type_integer
	);
	public $column_defaults = array(
		'stats_total' => 0,
		'stats_success' => 0,
		'stats_failure' => 0,
		'status' => 0
	);
	public $has_many = array(
		'tests' => array(
			'class' => "zest\\Test",
			'foreign_key' => 'project'
		)
	);
}
