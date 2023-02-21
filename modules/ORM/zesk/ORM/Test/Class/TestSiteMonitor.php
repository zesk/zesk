<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;

class Class_TestSiteMonitor extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Site' => __NAMESPACE__ . '\\' . 'Test_Site',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Site' => self::TYPE_OBJECT,
	];
}
