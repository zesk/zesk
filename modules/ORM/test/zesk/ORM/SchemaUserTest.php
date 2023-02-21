<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\ORM;

use zesk\Database\Table;
use zesk\ORM\Test\ORMUnitTestSchemaUser;

/**
 *
 * @author kent
 *
 */
class SchemaUserTest extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL', 'ORM',
	];

	public function test_update_objects(): void {
		$object = $this->application->ormFactory(ORMUnitTestSchemaUser::class);
		$database = $object->database();
		$table = $object->table();
		$database->query("DROP TABLE IF EXISTS $table");

		$this->assertInstanceOf(ORMUnitTestSchemaUser::class, $object);
		$sqlStatements = Schema::update_object($object);

		$this->assertNotCount(0, $sqlStatements);
		$this->assertFalse($database->tableExists($table));

		$database->queries($sqlStatements);

		$this->assertTrue($database->tableExists($table));

		$sqlStatements = Schema::update_object($object);
		$this->assertCount(0, $sqlStatements);

		$table = $object->database()->databaseTable($object->table());
		$this->assertInstanceOf(Table::class, $table);

		foreach ([$object->column_email(), $object->columnLogin(), $object->column_password()] as $column) {
			$this->assertTrue($table->hasColumn($column), "table has $column");
		}
	}
}
