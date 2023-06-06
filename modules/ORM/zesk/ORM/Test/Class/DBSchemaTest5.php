<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_Base;
use zesk\ORM\Test\DBSchemaTest5;

class Class_DBSchemaTest5 extends Class_Base {
	public function initialize(): void {
		parent::initialize();
		$this->table = DBSchemaTest5::$test_table;
	}

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Hash' => self::TYPE_STRING,
		'Phrase' => self::TYPE_STRING,
		'Created' => self::TYPE_CREATED,
		'Modified' => self::TYPE_MODIFIED,
		'Status' => self::TYPE_INTEGER,
		'IsOrganic' => self::TYPE_STRING,
		'LastUsed' => self::TYPE_TIMESTAMP,
	];
}
