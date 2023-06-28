<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_Base;

class Class_TestAccount extends Class_Base
{
	public string $id_column = 'ID';

	public array $has_one = [
		'Primary_Test_Site' => __NAMESPACE__ . '\\' . 'Test_Site',
		'Recent_Test_Site' => __NAMESPACE__ . '\\' . 'Test_Site',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Primary_Site' => self::TYPE_OBJECT,
		'Recent_Site' => self::TYPE_OBJECT,
		'Cancelled' => self::TYPE_TIMESTAMP,
	];
}
