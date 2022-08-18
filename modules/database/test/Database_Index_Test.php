<?php declare(strict_types=1);
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
class Database_Index_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	public function mytesttable() {
		$database = $this->application->database_registry();
		$table = new Database_Table($database, 'new_table');
		return $table;
	}

	/**
	 * @expectedException zesk\Exception_NotFound
	 */
	public function test_add_column_not_found(): void {
		$table = $this->mytesttable();

		$x = new Database_Index($table, 'testindex', [], Database_Index::TYPE_INDEX);

		$x->addColumn('Friday');
	}

	public function test_main(): void {
		$table = $this->mytesttable();
		$name = 'index_with_a_name';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, [], $type);

		$sqlType = '';
		Database_Index::determineType($sqlType);

		$x->name();

		$x->table();

		$x->columns();

		$x->columnCount();

		$x->type();

		$mixed = new Database_Column($table, 'Foo');
		$size = Database_Index::SIZE_DEFAULT;
		$x->addDatabaseColumn($mixed, $size);

		$x->sql_index_add();

		$x->sql_index_drop();

		$x->sql_index_type();

		$that = new Database_Index($table, 'another_name');
		$debug = false;
		$this->assert($x->isSimilar($that, $debug) === false);
		$this->assert($x->isSimilar($x, $debug) === true);
	}

	/**
	 * @expectedException zesk\Exception_Semantics
	 */
	public function test_name_required(): void {
		$table = $this->mytesttable();
		$name = '';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, [], $type);

		$mixed = new Database_Column($table, 'Foo');
		$size = Database_Index::SIZE_DEFAULT;
		$x->addDatabaseColumn($mixed, $size);

		$x->sql_index_drop();
	}

	public function test_determine_type(): void {
		$this->assert_equal(Database_Index::determineType('unique'), Database_Index::TYPE_UNIQUE);
		$this->assert_equal(Database_Index::determineType('unique key'), Database_Index::TYPE_UNIQUE);
		$this->assert_equal(Database_Index::determineType('primary key'), Database_Index::TYPE_PRIMARY);
		$this->assert_equal(Database_Index::determineType('primary'), Database_Index::TYPE_PRIMARY);
		$this->assert_equal(Database_Index::determineType('key'), Database_Index::TYPE_INDEX);
		$this->assert_equal(Database_Index::determineType('index'), Database_Index::TYPE_INDEX);
		$this->assert_equal(Database_Index::determineType(''), Database_Index::TYPE_INDEX);
		$this->assert_equal(Database_Index::determineType('Dude'), Database_Index::TYPE_INDEX);
		$this->assert_equal(Database_Index::determineType('MFNATCFF'), Database_Index::TYPE_INDEX);
	}
}
