<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Table_Test extends Test_Unit {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$db = $this->application->database_registry();
		$table_name = PHP::parseClass(__CLASS__) . '_' . __FUNCTION__;
		$table = new Database_Table($db, $table_name);

		$table->__toString();

		$table->database();

		$table->type();

		$table->name();

		$table->columns();

		$name = 'updated';

		try {
			$table->column($name);
			$this->assertFalse(true, 'Never gets here');
		} catch (Exception_Key $e) {
			$this->assertInstanceOf(Exception_Key::class, $e);
		}
		$table->columnAdd(new Database_Column($table, $name, [
			Database_Column::OPTION_SQL_TYPE => 'datetime',
			Database_Column::OPTION_NOT_NULL => false,
		]));
		$table->column($name);
		$table->previousColumn('thing');

		$name = 'foo';
		$this->assertFalse($table->hasIndex($name));

		$success = false;

		try {
			$name = 'name';
			$table->index($name);
		} catch (Exception_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success, 'name index should be not found');
		$table->indexes();

		$index = new Database_Index($table, 'un');
		$this->assertTrue($table->hasIndex($index->name()), 'Table has index');

		$dbCol = new Database_Column($table, 'IDs', [
			'sql_type' => 'varchar(32)',
		]);
		$exception_reserved = false;
		$table->columnAdd($dbCol, $exception_reserved);

		$table->sql_alter($table);

		$debug = $this->optionBool('debug');
		$that = $table;
		$this->assert($table->isSimilar($that, $debug) === true);
		$that = new Database_Table($db, 'someother');
		$this->assert($table->isSimilar($that, $debug) === false);

		echo basename(__FILE__) . ": success\n";
	}

	public function test_main2(): void {
		$db = $this->application->database_registry();
		$table_name = 'test_table';
		$x = new Database_Table($db, $table_name);

		$x->database();

		$this->assertFalse($x->hasColumn('foo'));

		$x->type();

		$x->defaultIndexStructure();

		$x->name();

		$x->columns();

		$x->columnNames();

		try {
			$x->column('Notfound');
			$this->assertFalse(true, 'never get here');
		} catch (Exception_Key $e) {
			$this->assertInstanceOf(Exception_Key::class, $e);
		}
		$name = 'foo';
		$x->columnAdd(new Database_Column($x, $name, [Database_Column::OPTION_SQL_TYPE => 'varchar(32)']));

		$x->column($name);

		$x->previousColumn('foo');

		$x->columns();

		$x->hasIndex('fun');

		$success = false;

		try {
			$x->index('foo');
		} catch (Exception_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success, 'name index should be not found');

		$x->indexes();

		$index_name = 'MyIndex';
		$index = new Database_Index($x, $index_name);

		$this->assertEquals($x->index($index_name), $index);
		$dbCol = new Database_Column($x, 'ID');
		$dbCol->setSQLType('bigint');
		$x->columnAdd($dbCol);

		$oldTable = new Database_Table($db, 'test_table_new');
		$x->sql_alter($oldTable);

		$that = $oldTable;
		$debug = false;
		$x->isSimilar($that, $debug);
	}
}
