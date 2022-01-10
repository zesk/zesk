<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Database_Query_Union_Test extends Test_Unit {
	protected array $load_modules = [
		"MySQL",
		"ORM",
	];

	public function test_main(): void {
		$table_name = "Database_Query_Union";

		$this->test_table($table_name);

		$db = $this->application->database_registry();
		$testx = new Database_Query_Union($db);

		$select = new Database_Query_Select($db);
		$testx->union($select);

		$testx->addWhat("ID");

		$table = $table_name;
		$alias = '';
		$testx->from($table, $alias);

		$sql = "INNER JOIN Foo F ON F.ID=B.Foo";

		$testx->addJoin($sql);

		$select->clearWhere();
		$select->appendWhere(["A" => null]);
		$select->addWhereSQL("COUNT(A) != COUNT(B)");

		$group_by = "ID";
		$testx->group_by([$group_by]);

		$order_by = null;
		$testx->order_by($order_by);

		$offset = 0;
		$limit = null;
		$testx->limit($offset, $limit);

		$testx->__toString();

		$testx->iterator();

		$class = "U";
		$options = [];
		$testx->orm_iterator($class, $options);

		$field = "id";
		$default = null;
		$testx->one($field, $default);

		$class = "User";
		$testx->orm($class);

		$field = "total";
		$testx->one_integer($field, 0);

		$field = null;
		$default = 0;
		$testx->integer($field, $default);

		$key = false;
		$value = false;
		$default = false;
		$testx->to_array($key, $value, $default);

		$testx->database();

		$class = Server::class;
		$testx->setORMClass($class);
	}
}
