<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/index.inc $
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
class Database_Index_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL"
	);
	function mytesttable() {
		$database = $this->application->database_factory();
		$table = new Database_Table($database, "new_table");
		return $table;
	}
	/**
	 * @expected_exception zesk\Exception_NotFound
	 */
	function test_add_column_not_found() {
		$table = $this->mytesttable();
		
		$x = new Database_Index($table, "testindex", Database_Index::Index);
		
		$x->column_add("Friday");
	}
	function test_main() {
		$table = $this->mytesttable();
		$name = 'index_with_a_name';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, $type);
		
		$sqlType = null;
		Database_Index::determineType($sqlType);
		
		$lower = false;
		$x->name($lower);
		
		$x->table();
		
		$lower = false;
		$x->columns($lower);
		
		$x->column_count();
		
		$x->type();
		
		$mixed = new Database_Column($table, "Foo");
		$size = true;
		$x->column_add($mixed, $size);
		
		$x->sql_index_add();
		
		$x->sql_index_drop();
		
		$x->sql_index_type();
		
		$that = new Database_Index($table, "another_name");
		$debug = false;
		$this->assert($x->is_similar($that, $debug) === false);
		$this->assert($x->is_similar($x, $debug) === true);
	}
	/**
	 * @expectedException zesk\Exception_Semantics
	 */
	function test_name_required() {
		$table = $this->mytesttable();
		$name = '';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, $type);
		
		$mixed = new Database_Column($table, "Foo");
		$size = true;
		$x->column_add($mixed, $size);
		
		$x->sql_index_drop();
	}
	function test_determine_type() {
		$this->assert_equal(Database_Index::determineType("unique"), Database_Index::Unique);
		$this->assert_equal(Database_Index::determineType("unique key"), Database_Index::Unique);
		$this->assert_equal(Database_Index::determineType("primary key"), Database_Index::Primary);
		$this->assert_equal(Database_Index::determineType("primary"), Database_Index::Primary);
		$this->assert_equal(Database_Index::determineType("key"), Database_Index::Index);
		$this->assert_equal(Database_Index::determineType("index"), Database_Index::Index);
		$this->assert_equal(Database_Index::determineType(""), Database_Index::Index);
		$this->assert_equal(Database_Index::determineType(null), Database_Index::Index);
		$this->assert_equal(Database_Index::determineType("Dude"), Database_Index::Index);
		$this->assert_equal(Database_Index::determineType("MFNATCFF"), Database_Index::Index);
	}
}

