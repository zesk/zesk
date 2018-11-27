<?php
/**
 * @package zesk
 * @test_sandbox true
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Insert_Select_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL",
		"ORM",
	);

	public function test_main() {
		$db = $this->application->database_registry();
		$testx = new Database_Query_Insert_Select($db);

		$db = null;
		$table = "test_table";
		$testx->into($table);

		echo $testx->__toString();

		$mixed = null;
		$value = null;
		$success = false;

		try {
			$testx->what("*", $value);
		} catch (Exception_Semantics $e) {
			$success = true;
		}
		$this->assert($success);

		$testx->what(array(
			"A" => "B",
			"*C" => "UTC_TIMESTAMP()",
			"D" => "Table.Field",
		));

		$table = "from_table";
		$alias = 'X';
		$testx->from($table, $alias);

		$sql = "INNER JOIN join_table J ON X.JID=J.ID";
		$testx->join($sql);

		$k = null;
		$v = null;
		$testx->where("X.Thing|>=", '20');

		$order_by = null;
		$testx->order_by("Created");

		$group_by = null;
		$testx->group_by($group_by);

		$sql = strval($testx);
		$sql = preg_replace('/\s+/', ' ', $sql);

		$correct_sql = "INSERT INTO `test_table` ( `A`, `C`, `D` ) SELECT `B` AS `A`, UTC_TIMESTAMP() AS `C`, `Table`.`Field` AS `D` FROM `from_table` AS `X` INNER JOIN join_table J ON X.JID=J.ID WHERE `X`.`Thing` >= '20' ORDER BY Created";
		$this->assert_equal($sql, $correct_sql);
	}
}
