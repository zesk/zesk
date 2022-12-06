<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_Help extends Class_ORM {
	public string $id_column = 'id';

	public array $find_keys = [
		'target',
	];

	public array $column_types = [
		'id' => self::type_id,
		'target' => self::type_string,
		'type' => self::type_string,
		'title' => self::type_string,
		'placement' => self::type_string,
		'content' => self::type_string,
		'map' => self::type_serialize,
		'content_wraps' => self::type_serialize,
		'content_url' => self::type_string,
		'require_user' => self::type_integer,
		'active' => self::type_boolean,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'show_first' => self::type_timestamp,
		'show_recent' => self::type_timestamp,
		'show_count' => self::type_integer,
	];

	public array $column_defaults = [
		'type' => 'basic',
		'require_user' => true,
		'active' => true,
		'show_count' => 0,
		'content_url' => '',
		'placement' => 'auto',
	];

	public array $has_many = [
		'users' => [
			'class' => 'zesk\\Help_User',
			'foreign_key' => 'help',
			'far_key' => 'user',
		],
	];
}
