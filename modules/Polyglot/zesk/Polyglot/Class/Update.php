<?php
declare(strict_types=1);
namespace zesk\Polyglot;

use zesk\ORM\Class_Base;
use zesk\ORM\User;

/**
 * @see Update
 * @author kent
 *
 */
class Class_Update extends Class_Base
{
	public string $code_name = 'Polyglot_Update';

	public string $id_column = 'locale';

	public string $auto_column = '';

	public array $column_types = [
		'locale' => self::TYPE_STRING,
		'updated' => self::TYPE_TIMESTAMP,
		'user' => self::TYPE_OBJECT,
	];

	public array $has_one = [
		'user' => User::class,
	];

	protected string $database_group = Token::class;
}
