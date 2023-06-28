<?php declare(strict_types=1);
namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

/**
 *
 * @author kent
 *
 */
class Class_TestDBQueryObject extends Class_Base
{
	public string $id_column = 'id';

	public array $column_types = [
		'id' => self::TYPE_ID,
		'foo' => self::TYPE_STRING,
	];

	public function schema(ORMBase $object): string|array|Schema
	{
		return 'CREATE TABLE {table} ( id integer unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT, foo varchar(128) )';
	}
}
