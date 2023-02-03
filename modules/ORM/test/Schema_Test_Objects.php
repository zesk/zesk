<?php
declare(strict_types=1);

namespace zesk\ORM;

use zesk\Database;
use zesk\Database_Column;
use zesk\Database_Data_Type;
use zesk\Database_Parser;
use zesk\Database_SQL;
use zesk\Exception_NotFound;

/**
 *
 */
class Class_ORMUnitTest_Schema_User extends Class_User {
	public function configureColumns(ORMBase $object): void {
		$this->configureFromSQL();
	}
}

/**
 *
 */
class ORMUnitTest_Schema_User extends User {
}
