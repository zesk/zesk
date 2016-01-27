<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/column.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Test_Database_Column extends Test_Unit {

	function test_main() {
		$db = Database::factory();

		$table = new Database_Table($db, __METHOD__);

		$name = "dude";
		$sqlType = null;
		$options = false;
		$x = new Database_Column($table, $name, $sqlType, $options);

		$name = 'dude';
		$x->name($name);

		$x->previous_name();

		$that = new Database_Column($table, "name", "varchar(16)");
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
