<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

class Database_Column_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$db = $this->application->databaseRegistry();

		$table = new Database_Table($db, __METHOD__);

		$name = 'dude';
		$x = new Database_Column($table, $name);

		$this->assertEquals($name, $x->name());

		$randomName = 'col' . $this->randomHex(12);
		$x->setName($randomName);

		$this->assertEquals($randomName, $x->name());

		$this->assertEquals('', $x->previousName());
		$x->setPreviousName($randomName);
		$this->assertEquals($randomName, $x->previousName());

		$that = new Database_Column($table, 'name', [
			'sql_type' => 'varchar(16)',
		]);
		$debug = false;
		$x->isSimilar($that, $debug);

		$x->hasSQLType();

		$x->sqlType();

		$checkEmpty = false;
		$x->hasDefaultValue($checkEmpty);

		$x->setDefaultValue(null);

		$x->defaultValue();

		$x->previousName();

		$x->binary();

		$x->primaryKey();

		$on_off = true;
		$x->setPrimaryKey($on_off);

		$x->isIncrement();

		$name = 'sue';
		$type = 'INDEX';
		$x->addIndex($name, $type);

		$x->indexesTypes();

		$x->required();

		$type = '';
		$x->isIndex($type);
	}

	/**
	 * @param array $expected
	 * @param Database_Column $a
	 * @param Database_Column $b
	 * @return void
	 * @dataProvider data_differences
	 */
	public function test_differences(array $expected, Database_Column $a, Database_Column $b): void {
		$result_a = $a->differences($b);
		$result_b = $b->differences($a);
		$result_a_keys = array_keys($result_a);
		$result_b_keys = array_keys($result_b);
		sort($result_a_keys);
		sort($result_b_keys);
		sort($expected);
		$this->assertEquals($result_a_keys, $result_b_keys);
		$this->assertEquals($expected, $result_b_keys);
	}

	public static function data_differences(): array {
		$this->setUp();
		$db = $this->application->databaseRegistry();
		$table = new Database_Table($db, __METHOD__);
		$col1 = new Database_Column($table, 'col1', ['sql_type' => 'varchar(32)', 'not null' => true]);
		$col2 = new Database_Column($table, 'col1', ['sql_type' => 'varchar(32)', 'not null' => false]);
		return [
			[[], $col1, $col1],
			[['required'], $col1, $col2],
		];
	}
}
