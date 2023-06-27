<?php
declare(strict_types=1);

namespace zesk\Session;

use zesk\ORM\Class_Base;
use zesk\ORM\User;

/**
 * @see Session
 * @author kent
 *
 */
class Class_SessionORM extends Class_Base
{
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
	];

	public string $code_name = 'Session';

	public array $column_defaults = [
		'data' => [],
		'sequence_index' => 0,
		'ip' => '127.0.0.1',
	];
}
