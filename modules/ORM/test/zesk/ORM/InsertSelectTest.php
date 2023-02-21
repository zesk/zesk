<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\ORM\Database\Query\InsertSelect;

class InsertSelectTest extends ORMUnitTest {
	protected array $load_modules = [
		'MySQL', 'ORM',
	];

	public function test_main(): void {
		$db = $this->application->databaseRegistry();
		$query = new InsertSelect($db);

		$this->assertEquals('', $query->__toString());

		$table = 'from_table';
		$alias = 'X';
		$query->from($table, $alias);

		$table = 'test_table';
		$query->into($table);

		$query->appendWhat([
			'A' => 'B', '*C' => 'UTC_TIMESTAMP()', 'D' => 'Table.Field',
		]);

		$sql = 'INNER JOIN join_table J ON X.JID=J.ID';
		$query->join($sql);

		$query->addWhere('X.Thing|>=', '20');

		$query->setOrderBy(['Created']);

		$query->setGroupBy([1]);

		$sql = strval($query);
		$sql = preg_replace('/\s+/', ' ', $sql);

		$correct_sql = 'INSERT INTO `test_table` ( `A`, `C`, `D` ) SELECT `B` AS `A`, UTC_TIMESTAMP() AS `C`, `Table`.`Field` AS `D` FROM `from_table` AS `X` INNER JOIN join_table J ON X.JID=J.ID WHERE `X`.`Thing` >= \'20\' GROUP BY 1 ORDER BY Created';
		$this->assertEquals($sql, $correct_sql);
	}
}
