<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database;

use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\PHP;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class TableTest extends TestCase {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$db = $this->application->databaseRegistry();
		$table_name = PHP::parseClass(__CLASS__) . '_' . __FUNCTION__;
		$table = new Table($db, $table_name);

		$table->__toString();

		$table->database();

		$table->type();

		$table->name();

		$table->columns();

		$name = 'updated';

		try {
			$table->column($name);
			$this->fail('Never gets here');
		} catch (KeyNotFound $e) {
			$this->assertInstanceOf(KeyNotFound::class, $e);
		}
		$table->columnAdd(new Column($table, $name, [
			Column::OPTION_SQL_TYPE => 'datetime',
			Column::OPTION_NOT_NULL => false,
		]));
		$table->column($name);
		$table->previousColumn('thing');

		$name = 'foo';
		$this->assertFalse($table->hasIndex($name));

		$success = false;

		try {
			$name = 'name';
			$table->index($name);
		} catch (NotFoundException $e) {
			$success = true;
		}
		$this->assertTrue($success, 'name index should be not found');
		$table->indexes();

		$index = new Index($table, 'un');
		$this->assertTrue($table->hasIndex($index->name()), 'Table has index');

		$dbCol = new Column($table, 'IDs', [
			'sql_type' => 'varchar(32)',
		]);
		$exception_reserved = false;
		$table->columnAdd($dbCol, $exception_reserved);

		$table->sql_alter($table);

		$debug = Types::toBool($this->option('debug'), false);
		$that = $table;
		$this->assertTrue($table->isSimilar($that, $debug));
		$that = new Table($db, 'some_other');
		$this->assertFalse($table->isSimilar($that, $debug));
	}

	public function test_main2(): void {
		$db = $this->application->databaseRegistry();
		$table_name = 'test_table';
		$x = new Table($db, $table_name);

		$x->database();

		$this->assertFalse($x->hasColumn('foo'));

		$x->type();

		$x->defaultIndexStructure();

		$x->name();

		$x->columns();

		$x->columnNames();

		try {
			$x->column('Notfound');
			$this->fail('never get here');
		} catch (KeyNotFound $e) {
			$this->assertInstanceOf(KeyNotFound::class, $e);
		}
		$name = 'foo';
		$x->columnAdd(new Column($x, $name, [Column::OPTION_SQL_TYPE => 'varchar(32)']));

		$x->column($name);

		$x->previousColumn('foo');

		$x->columns();

		$x->hasIndex('fun');

		$success = false;

		try {
			$x->index('foo');
		} catch (NotFoundException $e) {
			$success = true;
		}
		$this->assertTrue($success, 'name index should be not found');

		$x->indexes();

		$index_name = 'MyIndex';
		$index = new Index($x, $index_name);

		$this->assertEquals($x->index($index_name), $index);
		$dbCol = new Column($x, 'ID');
		$dbCol->setSQLType('bigint');
		$x->columnAdd($dbCol);

		$oldTable = new Table($db, 'test_table_new');
		$x->sql_alter($oldTable);

		$that = $oldTable;
		$debug = false;
		$x->isSimilar($that, $debug);
	}
}
