<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/index.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
use zesk\Database_Table;
use zesk\Database_Index;

class Test_Database_Index extends Test_Unit {

	function test_main() {
		$database = $this->application->database_factory(zesk::get('DB_URL'));
		$table = new Database_Table($database, "new_table");
		$name = '';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, $type);

		$sqlType = null;
		Database_Index::determineType($sqlType);

		$lower = false;
		$x->name($lower);

		$x->table();

		$lower = false;
		$x->columns($lower);

		$x->columnCount();

		$x->type();

		$mixed = "Foo";
		$size = true;
		$x->column_add($mixed, $size);

		$mixed = null;
		$size = true;
		$x->column_add("Friday", $size);

		$x->typeSQL();

		$x->createSQL();

		$x->dropSQL();

		$that = new Database_Index($table, "another_name");
		$debug = false;
		$this->assert($x->is_similar($that, $debug) === false);
		$this->assert($x->is_similar($x, $debug) === true);

		$writer = new XML_Writer();
		$x->toXML($writer);

		echo basename(__FILE__) . ": success\n";
	}
}

