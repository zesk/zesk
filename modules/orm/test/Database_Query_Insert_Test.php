<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Insert_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
		"ORM",
	];

	public function test_Database_Query_Insert(): void {
		/**
		 * $URL:
		 * https://code.marketacumen.com/zesk/trunk/classes/database/query/test/database_query_insert_test.inc
		 * $
		 *
		 * @package zesk
		 * @subpackage test
		 * @author Kent Davidson <kent@marketacumen.com>
		 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
		 */
		$db = $this->application->database_registry();
		$testx = new Database_Query_Insert($db);

		$table = null;
		$testx->into($table);

		$name = null;
		$value = null;
		$testx->value($name, $value);

		$values = [
			"ID" => "23",
		];
		$testx->values($values);

		$low_priority = null;
		$testx->low_priority($low_priority);

		$testx->replace();

		$testx->__toString();

		$columns = [
			"ID",
		];
		$testx->valid_columns($columns);

		$testx->database();

		$class = null;
		$testx->orm_class($class);
	}
}
