<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

class Database_Column_Test extends Test_Unit {
	protected array $load_modules = [
		'MySQL',
	];

	public function test_main(): void {
		$db = $this->application->database_registry();
		var_dump($this->application->database_module()->options());
		$table = new Database_Table($db, __METHOD__);

		$name = 'dude';
		$x = new Database_Column($table, $name);

		$x->name($name);

		$x->previousName();

		$that = new Database_Column($table, 'name', [
			'sql_type' => 'varchar(16)',
		]);
		$debug = false;
		$x->isSimilar($db, $that, $debug);

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

		$name = "sue";
		$type = 'INDEX';
		$x->addIndex($name, $type);

		$x->indexesTypes();

		$x->required();

		$type = '';
		$x->isIndex($type);
	}
}
