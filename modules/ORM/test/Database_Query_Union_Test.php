<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Database_Query_Union_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
		'ORM',
	];

	public function test_main(): void {
		$table_name = 'Database_Query_Union';

		$this->test_table($table_name);

		$db = $this->application->database_registry();
		$testx = new Database_Query_Union($db);

		$select = new Database_Query_Select($db);
		$testx->union($select);

		$testx->addWhat('ID');

		$table = $table_name;
		$alias = '';
		$testx->from($table, $alias);

		$sql = 'INNER JOIN Foo F ON F.ID=B.Foo';

		$testx->addJoin($sql);

		$select->clearWhere();
		$select->appendWhere(['A' => null]);
		$select->addWhereSQL('COUNT(A) != COUNT(B)');

		$group_by = 'ID';
		$testx->setGroupBy([$group_by]);

		$order_by = null;
		$testx->setOrderBy([]);

		$testx->setOffsetLimit(10, 100);

		$testx->__toString();

		$testx->database();

		$class = Server::class;
		$testx->setORMClass($class);
	}
}
