<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/table.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Test_Database_Table extends Test_Unit {

	function test_main() {
		$db = $this->application->database_factory(zesk::get('DB_URL'));
		$table_name = null;
		$type = false;
		$x = new zesk\Database_Table($db, $table_name, $type);

		$x->__toString();

		$x->database();

		$x->type();

		$x->name();

		$x->columns();

		$name = null;
		$x->column($name);

		$name = null;
		$x->old_Column($name);

		$x->column_Count();

		$name = null;
		$x->hasIndex($name);

		$name = null;
		$x->index($name);

		$x->indexCount();

		$x->indexes();

		$index = new zesk\Database_Index($x, "un");
		$x->index_add($index);

		$indexes = array();
		$x->setIndexes($indexes);

		$dbCol = new zesk\Database_Column("IDs", "varchar(32)");
		$exception_reserved = false;
		$x->add_Column($dbCol, $exception_reserved);

		$x->toSQL();

		$x->alterSQL($x);

		$that = $x;
		$debug = false;
		$this->assert($x->is_similar($that, $debug) === true);
		$that = new zesk\Database_Table($db, "someother", $debug);
		$this->assert($x->is_similar($that, $debug) === false);

		echo basename(__FILE__) . ": success\n";
	}

	function test_main2() {
		$db = $this->application->database_factory();
		$table_name = 'test_table';
		$type = false;
		$testx = new zesk\Database_Table($db, $table_name, $type);

		$testx->database();

		$name = null;
		$testx->has_Column($name);

		$testx->type();

		$testx->default_index_structure();

		$testx->name();

		$testx->columns();

		$testx->column_names();

		$name = null;
		$testx->column($name);

		$name = null;
		$testx->old_Column($name);

		$testx->column_Count();

		$name = null;
		$testx->hasIndex($name);

		$name = null;
		$testx->index($name);

		$testx->indexCount();

		$testx->indexes();

		$index = new zesk\Database_Index($testx, "MyIndex");
		$testx->index_add($index);

		$indexes = array(
			$index
		);
		$testx->setIndexes($indexes);

		$dbCol = new zesk\Database_Column("ID", "bigint");
		$exception_reserved = false;
		$testx->add_Column($dbCol, $exception_reserved);

		$testx->toSQL();

		$oldTable = new zesk\Database_Table($db, "test_table_new");
		$testx->alterSQL($oldTable);

		$that = $oldTable;
		$debug = false;
		$testx->is_similar($that, $debug);

		echo basename(__FILE__) . ": success\n";
	}
}
