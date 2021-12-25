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
		"MySQL",
	];

	public function test_main(): void {
		$db = $this->application->database_registry();

		$table = new Database_Table($db, __METHOD__);

		$name = "dude";
		$x = new Database_Column($table, $name);

		$name = 'dude';
		$x->name($name);

		$x->previous_name();

		$that = new Database_Column($table, "name", [
			"sql_type" => "varchar(16)",
		]);
		$debug = false;
		$x->is_similar($db, $that, $debug);

		$x->has_sql_type();

		$x->sql_type();

		$checkEmpty = false;
		$x->has_default_value($checkEmpty);

		$x->default_value();

		$x->previous_name();

		$x->binary();

		$x->primary_key();

		$on_off = true;
		$x->primary_key($on_off);

		$x->is_increment();

		$name = null;
		$type = 'INDEX';
		$x->index_add($name, $type);

		$x->indexes_types();

		$x->required();

		$type = '';
		$x->is_index($type);

		echo basename(__FILE__) . ": success\n";
	}
}
