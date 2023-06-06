<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_Base;

class Class_DBSchemaTest4 extends Class_Base {
	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest4::$test_table;
	}

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Depth' => self::TYPE_INTEGER,
		'CodeName' => self::TYPE_STRING,
		'Name' => self::TYPE_STRING,
	];
}
