<?php
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
	protected $load_modules = array(
		"MySQL",
		"ORM"
	);
	function test_Database_Query_Insert() {
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
		
		$values = array(
			"ID" => "23"
		);
		$testx->values($values);
		
		$low_priority = null;
		$testx->low_priority($low_priority);
		
		$testx->replace();
		
		$testx->__toString();
		
		$columns = array(
			"ID"
		);
		$testx->valid_columns($columns);
		
		$testx->database();
		
		$class = null;
		$testx->object_class($class);
	}
}
