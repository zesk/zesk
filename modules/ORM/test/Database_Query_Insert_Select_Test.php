<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception_Semantics;

class Database_Query_Insert_Select_Test extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL', 'ORM',
	];

	public function test_main(): void {
		$db = $this->application->databaseRegistry();
		$testx = new Database_Query_Insert_Select($db);

		$this->assertEquals('', $testx->__toString());

		$table = 'from_table';
		$alias = 'X';
		$testx->from($table, $alias);

		$table = 'test_table';
		$testx->into($table);

		$testx->appendWhat([
			'A' => 'B', '*C' => 'UTC_TIMESTAMP()', 'D' => 'Table.Field',
		]);

		$sql = 'INNER JOIN join_table J ON X.JID=J.ID';
		$testx->join($sql);

		$testx->addWhere('X.Thing|>=', '20');

		$order_by = null;
		$testx->setOrderBy(['Created']);

		$group_by = null;
		$testx->setGroupBy([1]);

		$sql = strval($testx);
		$sql = preg_replace('/\s+/', ' ', $sql);

		$correct_sql = 'INSERT INTO `test_table` ( `A`, `C`, `D` ) SELECT `B` AS `A`, UTC_TIMESTAMP() AS `C`, `Table`.`Field` AS `D` FROM `from_table` AS `X` INNER JOIN join_table J ON X.JID=J.ID WHERE `X`.`Thing` >= \'20\' GROUP BY 1 ORDER BY Created';
		$this->assertEquals($sql, $correct_sql);
	}
}
