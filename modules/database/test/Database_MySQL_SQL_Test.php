<?php
declare(strict_types=1);

namespace zesk;

class Database_MySQL_SQL_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	public function sql() {
		$db = $this->application->database_registry();
		$sql = $db->sql();
		return $sql;
	}

	public function test_function_date_add(): void {
		$sqldatetime = 'last_login';
		$hours = 2;
		$sql = $this->sql();
		$sql->function_date_add($sqldatetime, $hours);
	}

	public function test_delete(): void {
		$options = [
			'ignore' => true,
		];
		$where = [
			'*0' => '1',
		];
		$sql = $this->sql();
		$sql->delete('test', $where, $options);
	}

	public function test_drop_table(): void {
		$table = 'test';
		$sql = $this->sql();
		$sql->drop_table($table);
	}

	public function test_group_by(): void {
		$sql = $this->sql();
		$this->assertEquals(' GROUP BY dude', $sql->group_by('dude'));
		$this->assertEquals(' GROUP BY dude, pal', $sql->group_by(['dude', 'pal']));
	}

	public function test_hex(): void {
		$sql = $this->sql();
		$data = 'data';
		$this->assert_equal($sql->function_hex($data), 'HEX(data)');
	}

	public function test_insert(): void {
		$g = $this->sql();
		$t = 'table';
		$arr = [
			'A' => 'B',
		];
		$low_priority = false;
		$sql = $g->insert($t, $arr, [
			'low_priority' => $low_priority,
		]);
		$sql = preg_replace('/\s+/', ' ', $sql);
		$this->assert_equal($sql, 'INSERT INTO `table` ( `A` ) VALUES ( \'B\' )', "$sql === \"INSERT INTO `table` ( `A` ) VALUES ( 'B' )\"");

		$t = 'table';
		$arr = [
			'Foo' => 1,
			'Why' => 'Du\'de',
		];
		$low_priority = false;
		$sql = $g->insert($t, $arr, [
			'low_priority' => $low_priority,
		]);
		$sql = preg_replace('/\s+/', ' ', $sql);
		$sql_compare = 'INSERT INTO `table` ( `Foo`, `Why` ) VALUES ( 1, \'Du\\\'de\' )';
		$this->assert_equal($sql, $sql_compare, "$sql === $sql_compare");

		echo basename(__FILE__) . ": success\n";
	}

	public function test_now(): void {
		$sql = $this->sql();
		$sql->now();
		echo basename(__FILE__) . ": success\n";
	}

	public function data_order_by() {
		return [
			['', '', ''],
			[[], '', ''],
			['a', '', ' ORDER BY a'],
			['-a', '', ' ORDER BY a DESC'],
			['-a;-b', '', ' ORDER BY a DESC, b DESC'],
			['-a;b;c;-d', '', ' ORDER BY a DESC, b, c, d DESC'],
			['a desc', '', ' ORDER BY a desc'],
			['a desc;b asc', '', ' ORDER BY a desc, b asc'],
			['-a;b ASC;c;d DESC', '', ' ORDER BY a DESC, b ASC, c, d DESC'],
			[['-a'], '', ' ORDER BY a DESC'],
			[['-a', '-b'], '', ' ORDER BY a DESC, b DESC'],
			[['-a', 'b', 'c', '-d'], '', ' ORDER BY a DESC, b, c, d DESC'],
			[['a DESC'], '', ' ORDER BY a DESC'],
			[['a DESC', 'b DESC'], '', ' ORDER BY a DESC, b DESC'],
			[['a DESC', 'b', 'c', 'd DESC'], '', ' ORDER BY a DESC, b, c, d DESC'],
			/* Prefixes */ ['', 'X', ''],
			[[], 'X', ''],
			['a', 'X', ' ORDER BY X.a'],
			['-a', 'X', ' ORDER BY X.a DESC'],
			['-a;-b', 'X', ' ORDER BY X.a DESC, X.b DESC'],
			['-a;b;c;-d', 'X', ' ORDER BY X.a DESC, X.b, X.c, X.d DESC'],
			['a desc', 'X', ' ORDER BY X.a desc'],
			['a desc;T.b asc', 'X', ' ORDER BY X.a desc, T.b asc'],
			['-a;b ASC;c;T.d DESC', 'X', ' ORDER BY X.a DESC, X.b ASC, X.c, T.d DESC'],
			[['-a'], 'X', ' ORDER BY X.a DESC'],
			[['-a', '-b'], 'X', ' ORDER BY X.a DESC, X.b DESC'],
			[['-a', 'b', 'c', '-d'], 'X', ' ORDER BY X.a DESC, X.b, X.c, X.d DESC'],
			[['P.a DESC'], 'X', ' ORDER BY P.a DESC'],
			[['a DESC', 'b DESC'], 'X', ' ORDER BY X.a DESC, X.b DESC'],
			[['a DESC', 'b', 'Y.c', 'd DESC'], 'X', ' ORDER BY X.a DESC, X.b, Y.c, X.d DESC'],
		];
	}

	/**
	 * @dataProvider data_order_by
	 * @return void
	 */
	public function test_order_by(array|string $order_by, string $prefix, string $expected): void {
		$this->assertEquals($expected, $this->sql()->order_by($order_by, $prefix));
	}

	/**
	 *
	 */
	public function test_quote_column(): void {
		$sql = $this->sql();
		$name = 'user_name';
		$this->assertEquals('`user_name`', $sql->quoteColumn($name));
	}

	public function test_quote_text(): void {
		$sql = $this->sql();
		$name = 'Dasterdly';
		$sql->quoteText($name);
	}

	public function test_quoteTable(): void {
		$sql = $this->sql();
		$table = null;
		$this->assert($sql->quoteTable('foo') === '`foo`');
		echo basename(__FILE__) . ": success\n";
	}

	public static function clean_white($sql) {
		return trim(preg_replace('/\s+/', ' ', $sql));
	}

	public function test_replace(): void {
		$sql = $this->sql();
		$t = 'table';
		$arr = [
			'A' => 'B',
		];
		$result = preg_replace('/\s+/', ' ', $sql->insert($t, $arr, [
			'verb' => 'REPLACE',
			'low_priority' => false,
		]));
		$this->assert($result === 'REPLACE INTO `table` ( `A` ) VALUES ( \'B\' )', "$sql === \"REPLACE INTO `table` ( `A` ) VALUES ( 'B' )\"");
		echo basename(__FILE__) . ": success\n";
	}

	public function test_select(): void {
		$sql = $this->sql();

		$where = null;
		$group_by = false;
		$order_by = false;
		$offset = 0;
		$limit = -1;
		$actual = $sql->select([
			'what' => '*',
			'tables' => ['thing'],
			'where' => $where,
			'group_by' => $group_by,
			'order_by' => $order_by,
			'offset' => $offset,
			'limit' => $limit,
		]);
		$this->assertEquals('SELECT * FROM `thing`', $actual);
		echo basename(__FILE__) . ": success\n";
	}

	public function test_table_as(): void {
		$sql = $this->sql();

		$table = 'John';
		$as = 'Nancy';
		$this->assert_equal('`John` AS `Nancy`', $sql->table_as($table, $as));
	}

	public function test_unhex(): void {
		$sql = $this->sql();
		$data = 'ABACAB';
		$sql->function_unhex($data);
	}

	public function test_update(): void {
		$sql = $this->sql();
		$t = 'table';
		$arr = [
			'A' => 2,
			'B' => 4,
		];
		$where = [];
		$actual = $sql->update([
			'table' => $t,
			'values' => $arr,
			'where' => $where,
			'low_priority' => true,
		]);
		$actual = self::clean_white($actual);
		$expected = 'UPDATE LOW_PRIORITY `table` SET `A` = 2, `B` = 4';
		$this->assert_equal($actual, $expected);
	}

	public function test_now_utc(): void {
		$sql = $this->sql();
		$sql->now_utc();
		echo basename(__FILE__) . ": success\n";
	}

	public function test_where(): void {
		$sql = $this->sql();
		$where = [
			'A' => 'B',
			'C' => [
				'D',
				'E',
			],
		];
		$conj = 'AND';
		$prefix = '';
		$this->assert_equal(self::clean_white($sql->where($where, $conj, $prefix)), 'WHERE `A` = \'B\' AND (`C` = \'D\' OR `C` = \'E\')');
	}

	public function test_where_clause(): void {
		$sql = $this->sql();

		$arr = [];
		$conj = 'AND';
		$prefix_in = '';
		$this->assertEquals('', $sql->where_clause($arr, $conj, $prefix_in));

		$where = [
			'Q.Site' => [
				1,
				2,
				3,
			],
			'*Q.UTC|<=' => 'MIN(F.Stats_UTC)',
		];
		$this->assert($sql->where_clause($where) === '(`Q`.`Site` = 1 OR `Q`.`Site` = 2 OR `Q`.`Site` = 3) AND Q.UTC<=MIN(F.Stats_UTC)', $sql->where_clause($where));

		$this->assert($sql->where_clause([
				'*Q.UTC|<=' => 'MIN(F.Stats_UTC)',
			]) === 'Q.UTC<=MIN(F.Stats_UTC)');

		$this->assert($sql->where_clause([
				'*FOO' => 0,
			]) === 'FOO=0');
		$this->assert($sql->where_clause([
				'*FOO' => null,
			]) === 'FOO IS NULL');
		$this->assert($sql->where_clause([
				'*FOO' => '',
			]) === 'FOO=');

		$this->assert($sql->where_clause([
				'FOO' => 0,
			]) === '`FOO` = 0');
		$this->assert($sql->where_clause([
				'FOO' => null,
			]) === '`FOO` IS NULL', $sql->where_clause([
			'FOO' => null,
		]));
		$this->assert($sql->where_clause([
				'FOO' => '',
			]) === '`FOO` = \'\'');
	}
}
