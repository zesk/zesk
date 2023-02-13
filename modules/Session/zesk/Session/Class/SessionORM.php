<?php
declare(strict_types=1);

namespace zesk\Session;

use zesk\ORM\Class_Base;
use zesk\ORM\User;

/**
 * @see SessionORM
 * @author kent
 *
 */
class Class_SessionORM extends Class_Base {
	/**
	 * ID Column
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	public array $find_keys = [
		'cookie',
	];

	public array $has_one = [
		'user' => User::class,
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		SessionORM::MEMBER_TOKEN => self::TYPE_STRING,
		SessionORM::MEMBER_TYPE => self::TYPE_STRING,
		SessionORM::MEMBER_USER => self::TYPE_OBJECT,
		SessionORM::MEMBER_IP => self::TYPE_IP4,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
		SessionORM::MEMBER_EXPIRES => self::TYPE_DATETIME,
		'seen' => self::TYPE_DATETIME,
		'sequence_index' => self::TYPE_INTEGER,
		SessionORM::MEMBER_DATA => self::TYPE_SERIALIZE,
	];

	public string $code_name = 'Session';

	public array $column_defaults = [
		'data' => [],
		'sequence_index' => 0,
		'ip' => '127.0.0.1',
	];
}
