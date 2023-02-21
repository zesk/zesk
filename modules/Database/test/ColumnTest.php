<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database;

use zesk\PHPUnit\TestCase;

class ColumnTest extends TestCase {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$db = $this->application->databaseRegistry();

		$table = new Table($db, __METHOD__);

		$name = 'dude';
		$x = new Column($table, $name);

		$this->assertEquals($name, $x->name());

		$randomName = 'col' . $this->randomHex(12);
		$x->setName($randomName);

		$this->assertEquals($randomName, $x->name());

		$this->assertEquals('', $x->previousName());
		$x->setPreviousName($randomName);
		$this->assertEquals($randomName, $x->previousName());

		$that = new Column($table, 'name', [
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
	 * @param Column $a
	 * @param Column $b
	 * @return void
	 * @dataProvider data_differences
	 */
	public function test_differences(array $expected, mixed $a, mixed $b): void {
		$a = $this->applyClosures($a);
		$b = $this->applyClosures($b);
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
		$col1 = function () {
			$db = self::app()->databaseRegistry();
			$table = new Table($db, __METHOD__);
			return new Column($table, 'col1', ['sql_type' => 'varchar(32)', 'not null' => true]);
		};
		$col2 = function () {
			$db = self::app()->databaseRegistry();
			$table = new Table($db, __METHOD__);
			return new Column($table, 'col1', ['sql_type' => 'varchar(32)', 'not null' => false]);
		};
		return [
			[[], $col1, $col1],
			[['required'], $col1, $col2],
		];
	}
}
