<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/table.inc $
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
	protected $load_modules = array(
		"MySQL"
	);
	function test_main() {
		$db = $this->application->database_registry();
		$table_name = PHP::parse_class(__CLASS__) . '_' . __FUNCTION__;
		$type = false;
		$table = new Database_Table($db, $table_name, $type);
		
		$table->__toString();
		
		$table->database();
		
		$table->type();
		
		$table->name();
		
		$table->columns();
		
		$name = null;
		$table->column($name);
		
		$name = null;
		$table->previous_column("thing");
		
		$name = null;
		$table->has_index($name);
		
		$name = null;
		$table->index($name);
		
		$table->indexes();
		
		$index = new Database_Index($table, "un");
		$table->index_add($index);
		
		$indexes = array();
		$table->set_indexes($indexes);
		
		$dbCol = new Database_Column($table, "IDs", array(
			"sql_type" => "varchar(32)"
		));
		$exception_reserved = false;
		$table->column_add($dbCol, $exception_reserved);
		
		$table->sql_alter($table);
		
		$that = $table;
		$debug = false;
		$this->assert($table->is_similar($that, $debug) === true);
		$that = new Database_Table($db, "someother", $debug);
		$this->assert($table->is_similar($that, $debug) === false);
		
		echo basename(__FILE__) . ": success\n";
	}
	function test_main2() {
		$db = $this->application->database_registry();
		$table_name = 'test_table';
		$type = false;
		$testx = new Database_Table($db, $table_name, $type);
		
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
		$testx->previous_column($name);
		
		$testx->columns();
		
		$name = null;
		$testx->has_index($name);
		
		$name = null;
		$testx->index($name);
		
		$testx->indexes();
		
		$index = new Database_Index($testx, "MyIndex");
		$testx->index_add($index);
		
		$indexes = array(
			$index
		);
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
