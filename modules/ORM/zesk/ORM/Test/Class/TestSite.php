<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\Class_Base;

class Class_TestSite extends Class_Base {
	public string $id_column = 'ID';

	public array $has_one = [
		'Account' => __NAMESPACE__ . '\\' . 'TestAccount',
	];

	public array $column_types = [
		'ID' => self::TYPE_ID,
		'Name' => self::TYPE_STRING,
		'Account' => self::TYPE_OBJECT,
	];
}
