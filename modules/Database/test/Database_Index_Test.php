<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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

	public function mytesttable(): Database_Table {
		$database = $this->application->databaseRegistry();
		return new Database_Table($database, 'new_table');
	}

	/**
	 *
	 */
	public function test_add_column_not_found(): void {
		$this->expectException(Exception_NotFound::class);
		$table = $this->mytesttable();

		$x = new Database_Index($table, 'testindex', Database_Index::TYPE_INDEX);

		$x->addColumn('Friday');
	}

	public function test_main(): void {
		$table = $this->mytesttable();
		$name = 'index_with_a_name';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, $type);

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
		$this->assertFalse($x->isSimilar($that, $debug));
		$this->assertTrue($x->isSimilar($x, $debug));
	}

	/**
	 *
	 */
	public function test_name_required(): void {
		$this->expectException(Exception_Semantics::class);
		$table = $this->mytesttable();
		$name = '';
		$type = 'INDEX';
		$x = new Database_Index($table, $name, $type);

		$mixed = new Database_Column($table, 'Foo');
		$size = Database_Index::SIZE_DEFAULT;
		$x->addDatabaseColumn($mixed, $size);

		$x->sql_index_drop();
	}

	/**
	 * @param string $type
	 * @param string $expected
	 * @return void
	 * @dataProvider data_determine_type
	 */
	public function test_determine_type(string $type, string $expected): void {
		$this->assertEquals($expected, Database_Index::determineType($type));
	}

	public static function data_determine_type(): array {
		return [
			['unique', Database_Index::TYPE_UNIQUE],
			['unique key', Database_Index::TYPE_UNIQUE],
			['primary key', Database_Index::TYPE_PRIMARY],
			['primary', Database_Index::TYPE_PRIMARY],
			['key', Database_Index::TYPE_INDEX],
			['index', Database_Index::TYPE_INDEX],
			['', Database_Index::TYPE_INDEX],
			['Dude', Database_Index::TYPE_INDEX],
			['MFNATCFF', Database_Index::TYPE_INDEX],
		];
	}
}
