<?php
declare(strict_types=1);

namespace zesk\ORM\Test\Class;

use zesk\ORM\Class_User;
use zesk\ORM\ORMBase;

class Class_ORMUnitTestSchemaUser extends Class_User {
	public function configureColumns(ORMBase $object): void {
		$this->configureFromSQL();
	}
}
