<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\ORM\Schema;

class DBSchemaTest8 extends ORMBase {
	public static string $test_table = '';

	public function schema(): string|array|null|Schema {
		return 'CREATE TABLE `{table}` (
			`ID` int(11) unsigned NOT NULL auto_increment,
			`Hash` char(32) NOT NULL,
			`Size` bigint unsigned NOT NULL,
			PRIMARY KEY (ID)
		);';
	}
}
