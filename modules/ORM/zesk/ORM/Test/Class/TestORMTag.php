<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;
use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class Class_TestORMTag extends Class_Base
{
	public string $id_column = 'ID';

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Parent' => self::TYPE_OBJECT,
	];

	public array $has_one = [
		'Parent' => TestORM::class,
	];

	public function schema(ORMBase $object): string|array|Schema
	{
		$table = $this->table;
		return [
			"CREATE TABLE $table ( ID integer unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY, Name varchar(32) NOT NULL, Parent integer NOT NULL )",
		];
	}
}
