<?php
declare(strict_types=1);

namespace zesk\Job;

use zesk\ORM\Class_Base;
use zesk\ORM\Server;
use zesk\ORM\User;

/**
 * @see Job
 * @author kent
 *
 */
class Class_Job extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'code',
	];

	public array $has_one = [
		'user' => User::class,
		'server' => Server::class,
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'user' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
		'code' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'start' => self::TYPE_TIMESTAMP,
		'server' => self::TYPE_OBJECT,
		'priority' => self::TYPE_INTEGER,
		'pid' => self::TYPE_INTEGER,
		'completed' => self::TYPE_DATETIME,
		'updated' => self::TYPE_DATETIME,
		'duration' => self::TYPE_INTEGER,
		'died' => self::TYPE_INTEGER,
		'last_exit' => self::TYPE_INTEGER,
		'progress' => self::TYPE_DOUBLE,
		'hook' => self::TYPE_STRING,
		'hook_args' => self::TYPE_SERIALIZE,
		'data' => self::TYPE_SERIALIZE,
		'status' => self::TYPE_STRING,
	];

	public array $column_defaults = [
		'duration' => 0,
		'died' => 0,
		'status' => '',
	];

	public function initialize(): void {
	}
}
