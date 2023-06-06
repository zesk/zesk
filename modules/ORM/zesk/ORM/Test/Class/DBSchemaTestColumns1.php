<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_Base;

class Class_DBSchemaTestColumns1 extends Class_Base {
	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Hash' => self::TYPE_STRING,
		'Protocol' => self::TYPE_STRING,
		'Domain' => self::TYPE_OBJECT,
		'Port' => self::TYPE_INTEGER,
		'URI' => self::TYPE_OBJECT,
	];
}