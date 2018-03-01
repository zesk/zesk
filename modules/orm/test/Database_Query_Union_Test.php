<?php
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Union_Test extends Test_Unit {
	protected $load_modules = array(
		"MySQL",
		"ORM"
	);
	function test_main() {
		$table_name = "Database_Query_Union";
		
		$this->test_table($table_name);
		
		$db = $this->application->database_registry();
		$testx = new Database_Query_Union($db);
		
		$select = new Database_Query_Select($db);
		$testx->union($select);
		
		$what = null;
		$testx->what($what);
		
		$table = $table_name;
		$alias = '';
		$testx->from($table, $alias);
		
		$sql = null;
		$join_id = null;
		$testx->join($sql, $join_id);
		
		$k = null;
		$v = null;
		$testx->where($k, $v);
		
		$group_by = null;
		$testx->group_by($group_by);
		
		$order_by = null;
		$testx->order_by($order_by);
		
		$offset = 0;
		$limit = null;
		$testx->limit($offset, $limit);
		
		$testx->__toString();
		
		$testx->iterator();
		
		$class = "U";
		$options = array();
		$testx->orm_iterator($class, $options);
		
		$field = false;
		$default = false;
		$testx->one($field, $default);
		
		$class = "User";
		$testx->object($class);
		
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
	}
}
