<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/query/Database_Query_Select_Test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Select_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL",
		"ORM"
	);
	function test_main() {
		$table_name = "Database_Query_Select";
		
		$this->test_table($table_name);
		
		$db = $this->application->database_factory();
		$testx = new Database_Query_Select($db);
		
		$db = null;
		$mixed = null;
		$value = null;
		$testx->what($mixed, $value);
		
		$alias = '';
		$testx->from($table_name, $alias);
		
		$sql = null;
		$join_id = null;
		$testx->join($sql, $join_id);
		
		$k = null;
		$v = null;
		$testx->where($k, $v);
		
		$order_by = null;
		$testx->order_by($order_by);
		
		$group_by = null;
		$testx->group_by($group_by);
		
		$offset = 0;
		$limit = null;
		$testx->limit($offset, $limit);
		
		$testx->__toString();
		
		$testx->iterator();
		
		$class = null;
		$testx->object_iterator($class);
		
		$field = false;
		$default = false;
		$testx->one($field, $default);
		
		$testx->object("User");
		
		$field = null;
		$default = 0;
		$testx->one_integer($field, $default);
		
		$field = null;
		$default = 0;
		$testx->integer($field, $default);
		
		$key = false;
		$value = false;
		$default = false;
		$testx->to_array($key, $value, $default);
		
		$testx->database();
		
		$class = null;
		$testx->object_class($class);
		
		$db = $this->application->database_factory();
		$x = new Database_Query_Select($db);
		$x->from($table_name);
		$x->what(null, "ID");
		$x->where("ID", array(
			1,
			2,
			3,
			4
		));
		
		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (`ID` = 1 OR `ID` = 2 OR `ID` = 3 OR `ID` = 4)";
		
		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assert_equal($result, $valid_result);
		
		$x = new Database_Query_Select($db);
		$x->from($table_name);
		$x->what(null, "ID");
		$x->where("ID|!=|AND", array(
			1,
			2,
			3,
			4
		));
		$result = strval($x);
		
		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (`ID` != 1 AND `ID` != 2 AND `ID` != 3 AND `ID` != 4)";
		
		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		$this->assert($result === "$valid_result", "\"$result\" === \"$valid_result\"");
		
		$x = new Database_Query_Select($db);
		$x->from($table_name);
		$x->what(null, "ID");
		$x->where("*SUM(Total)|!=|AND", array(
			1,
			2,
			3,
			4
		));
		$result = strval($x);
		
		$result = strval($x);
		$valid_result = "SELECT ID FROM `Database_Query_Select` WHERE (SUM(Total)!=1 AND SUM(Total)!=2 AND SUM(Total)!=3 AND SUM(Total)!=4)";
		
		$result = preg_replace('/\s+/', ' ', trim($result));
		$valid_result = preg_replace('/\s+/', ' ', trim($valid_result));
		
		$this->assert($result === "$valid_result", "\"$result\" === \"$valid_result\"");
	}
}
