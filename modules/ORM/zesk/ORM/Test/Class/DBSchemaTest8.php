<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_Base;
use zesk\ORM\Test\DBSchemaTest8;

class Class_DBSchemaTest8 extends Class_Base {
	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Hash' => self::TYPE_STRING,
		'Size' => self::TYPE_INTEGER,
	];

	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest8::$test_table;
	}
}
