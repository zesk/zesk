<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Database;

use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\PHPUnit\TestCase;

/**
 *
 * @author kent
 *
 */
class IndexTest extends TestCase
{
	protected array $load_modules = [
		'MySQL',
	];

	public function mytesttable(): Table
	{
		$database = $this->application->databaseRegistry();
		return new Table($database, 'new_table');
	}

	/**
	 *
	 */
	public function test_add_column_not_found(): void
	{
		$this->expectException(NotFoundException::class);
		$table = $this->mytesttable();

		$x = new Index($table, 'testindex', Index::TYPE_INDEX);

		$x->addColumn('Friday');
	}

	public function test_main(): void
	{
		$table = $this->mytesttable();
		$name = 'index_with_a_name';
		$type = 'INDEX';
		$x = new Index($table, $name, $type);

		$sqlType = '';
		Index::determineType($sqlType);

		$x->name();

		$x->table();

		$x->columns();

		$x->columnCount();

		$x->type();

		$mixed = new Column($table, 'Foo');
		$size = Index::SIZE_DEFAULT;
		$x->addDatabaseColumn($mixed, $size);

		$x->sql_index_add();

		$x->sql_index_drop();

		$x->sql_index_type();

		$that = new Index($table, 'another_name');
		$debug = false;
		$this->assertFalse($x->isSimilar($that, $debug));
		$this->assertTrue($x->isSimilar($x, $debug));
	}

	/**
	 *
	 */
	public function test_name_required(): void
	{
		$this->expectException(SemanticsException::class);
		$table = $this->mytesttable();
		$name = '';
		$type = 'INDEX';
		$x = new Index($table, $name, $type);

		$mixed = new Column($table, 'Foo');
		$size = Index::SIZE_DEFAULT;
		$x->addDatabaseColumn($mixed, $size);

		$x->sql_index_drop();
	}

	/**
	 * @param string $type
	 * @param string $expected
	 * @return void
	 * @dataProvider data_determine_type
	 */
	public function test_determine_type(string $type, string $expected): void
	{
		$this->assertEquals($expected, Index::determineType($type));
	}

	public static function data_determine_type(): array
	{
		return [
			['unique', Index::TYPE_UNIQUE],
			['unique key', Index::TYPE_UNIQUE],
			['primary key', Index::TYPE_PRIMARY],
			['primary', Index::TYPE_PRIMARY],
			['key', Index::TYPE_INDEX],
			['index', Index::TYPE_INDEX],
			['', Index::TYPE_INDEX],
			['Dude', Index::TYPE_INDEX],
			['MFNATCFF', Index::TYPE_INDEX],
		];
	}
}
