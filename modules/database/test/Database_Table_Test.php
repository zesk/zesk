<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Table_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
	];

	public function test_main(): void {
		$db = $this->application->database_registry();
		$table_name = PHP::parse_class(__CLASS__) . '_' . __FUNCTION__;
		$table = new Database_Table($db, $table_name);

		$table->__toString();

		$table->database();

		$table->type();

		$table->name();

		$table->columns();

		$name = "updated";
		$table->column($name);

		$table->previous_column("thing");

		$name = "foo";
		$this->assertFalse($table->has_index($name));

		$success = false;

		try {
			$name = "name";
			$table->index($name);
		} catch (Exception_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success, "name index should be not found");
		$table->indexes();

		$index = new Database_Index($table, "un");
		$table->index_add($index);

		$indexes = [];
		$table->set_indexes($indexes);

		$dbCol = new Database_Column($table, "IDs", [
			"sql_type" => "varchar(32)",
		]);
		$exception_reserved = false;
		$table->column_add($dbCol, $exception_reserved);

		$table->sql_alter($table);

		$debug = $this->optionBool("debug");
		$that = $table;
		$this->assert($table->is_similar($that, $debug) === true);
		$that = new Database_Table($db, "someother");
		$this->assert($table->is_similar($that, $debug) === false);

		echo basename(__FILE__) . ": success\n";
	}

	public function test_main2(): void {
		$db = $this->application->database_registry();
		$table_name = 'test_table';
		$testx = new Database_Table($db, $table_name);

		$testx->database();

		$this->assertFalse($testx->hasColumn("foo"));

		$testx->type();

		$testx->default_index_structure();

		$testx->name();

		$testx->columns();

		$testx->column_names();

		$name = "foo";
		$testx->column($name);

		$testx->previous_column("foo");

		$testx->columns();

		$testx->has_index("fun");

		$success = false;

		try {
			$testx->index("foo");
		} catch (Exception_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success, "name index should be not found");

		$testx->indexes();

		$index = new Database_Index($testx, "MyIndex");
		$testx->index_add($index);

		$indexes = [
			$index,
		];
		$testx->set_indexes($indexes);

		$dbCol = new Database_Column($testx, "ID");
		$dbCol->sql_type("bigint");
		$exception_reserved = false;
		$testx->column_add($dbCol, $exception_reserved);

		$oldTable = new Database_Table($db, "test_table_new");
		$testx->sql_alter($oldTable);

		$that = $oldTable;
		$debug = false;
		$testx->is_similar($that, $debug);

		echo basename(__FILE__) . ": success\n";
	}
}
