<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\ORM;

use zesk\Database_Table;

/**
 *
 * @author kent
 *
 */
class Schema_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL', 'ORM',
	];

	public function initialize(): void {
		require_once __DIR__ . '/Schema_Test_Objects.php';
	}

	public function test_debug(): void {
		$value = Schema::debug();

		Schema::debug(true);
		$this->assertEquals(Schema::debug(), true);
		Schema::debug('Friday');
		$this->assertEquals(Schema::debug(), true);

		Schema::debug(false);
		$this->assertEquals(Schema::debug(), false);
		Schema::debug('Friday');
		$this->assertEquals(Schema::debug(), false);

		Schema::debug($value);
	}

	public function test_update_objects(): void {
		$object = $this->application->ormFactory(ORMUnitTest_Schema_User::class);
		$database = $object->database();
		$table = $object->table();
		$database->query("DROP TABLE $table");

		assert($object instanceof ORMUnitTest_Schema_User);
		$sqlStatements = Schema::update_object($object);

		$this->assertNotCount(0, $sqlStatements);
		$this->assertFalse($database->tableExists($table));

		$database->queries($sqlStatements);

		$this->assertTrue($database->tableExists($table));

		$sqlStatements = Schema::update_object($object);
		$this->assertCount(0, $sqlStatements);

		$table = $object->database()->databaseTable($object->table());
		$this->assertInstanceOf(Database_Table::class, $table);

		foreach ([$object->column_email(), $object->column_login(), $object->column_password()] as $column) {
			$this->assertTrue($table->hasColumn($column), "table has $column");
		}
	}
}
