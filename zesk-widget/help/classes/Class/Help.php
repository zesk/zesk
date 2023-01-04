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
class Class_Help extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'target',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'target' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'title' => self::TYPE_STRING,
		'placement' => self::TYPE_STRING,
		'content' => self::TYPE_STRING,
		'map' => self::TYPE_SERIALIZE,
		'content_wraps' => self::TYPE_SERIALIZE,
		'content_url' => self::TYPE_STRING,
		'require_user' => self::TYPE_INTEGER,
		'active' => self::TYPE_BOOL,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
		'show_first' => self::TYPE_TIMESTAMP,
		'show_recent' => self::TYPE_TIMESTAMP,
		'show_count' => self::TYPE_INTEGER,
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
