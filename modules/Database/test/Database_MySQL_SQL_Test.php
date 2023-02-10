<?php
declare(strict_types=1);

namespace zesk;

class Database_MySQL_SQL_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	public function sql(): Database_SQL {
		return $this->application->databaseRegistry()->sql();
	}

	public function test_function_date_add(): void {
		$column = 'last_login';
		$hours = $this->randomInteger(1, 20);
		$sql = $this->sql();
		$this->assertEquals("DATE_ADD(last_login, INTERVAL $hours SECOND)", $sql->function_date_add($column, $hours));
		$this->assertEquals("DATE_ADD(last_login, INTERVAL $hours HOUR)", $sql->function_date_add($column, $hours, Temporal::UNIT_HOUR));
	}

	public function test_delete(): void {
		$options = [
			'ignore' => true,
		];
		$where = [
			'*0' => '1',
		];
		$sql = $this->sql();
		$this->assertEquals('DELETE FROM `test` WHERE 0=1', $sql->delete('test', $where, $options));
	}

	public function test_drop_table(): void {
		$table = 'test';
		$sql = $this->sql();
		$this->assertEquals(['DROP TABLE IF EXISTS `test`'], $sql->drop_table($table));
	}

	public function test_group_by(): void {
		$sql = $this->sql();
		$this->assertEquals(' GROUP BY dude', $sql->group_by('dude'));
		$this->assertEquals(' GROUP BY dude, pal', $sql->group_by(['dude', 'pal']));
	}

	public function test_hex(): void {
		$sql = $this->sql();
		$data = 'data';
		$this->assertEquals('HEX(data)', $sql->function_hex($data));
	}

	public function test_insert(): void {
		$g = $this->sql();
		$t = 'table';
		$arr = [
			'A' => 'B',
		];
		$opts = [
			'low_priority' => false,
		];
		$sql = $g->insert($t, $arr, $opts);
		$sql = preg_replace('/\s+/', ' ', $sql);
		$this->assertEquals('INSERT INTO `table` ( `A` ) VALUES ( \'B\' )', $sql);

		$arr = [
			'Foo' => 1, 'Why' => 'Du\'de',
		];
		$sql = $g->insert($t, $arr, $opts);
		$sql = preg_replace('/\s+/', ' ', $sql);
		$this->assertEquals('INSERT INTO `table` ( `Foo`, `Why` ) VALUES ( 1, \'Du\\\'de\' )', $sql);
	}

	public function test_now(): void {
		$sql = $this->sql();
		$this->assertEquals('NOW()', $sql->now());
	}

	public static function data_order_by(): array {
		return [
			['', '', ''], [[], '', ''], ['a', '', ' ORDER BY a'], ['-a', '', ' ORDER BY a DESC'],
			['-a;-b', '', ' ORDER BY a DESC, b DESC'], ['-a;b;c;-d', '', ' ORDER BY a DESC, b, c, d DESC'],
			['a desc', '', ' ORDER BY a desc'], ['a desc;b asc', '', ' ORDER BY a desc, b asc'],
			['-a;b ASC;c;d DESC', '', ' ORDER BY a DESC, b ASC, c, d DESC'], [['-a'], '', ' ORDER BY a DESC'],
			[['-a', '-b'], '', ' ORDER BY a DESC, b DESC'],
			[['-a', 'b', 'c', '-d'], '', ' ORDER BY a DESC, b, c, d DESC'], [['a DESC'], '', ' ORDER BY a DESC'],
			[['a DESC', 'b DESC'], '', ' ORDER BY a DESC, b DESC'],
			[['a DESC', 'b', 'c', 'd DESC'], '', ' ORDER BY a DESC, b, c, d DESC'], /* Prefixes */ ['', 'X', ''],
			[[], 'X', ''], ['a', 'X', ' ORDER BY X.a'], ['-a', 'X', ' ORDER BY X.a DESC'],
			['-a;-b', 'X', ' ORDER BY X.a DESC, X.b DESC'],
			['-a;b;c;-d', 'X', ' ORDER BY X.a DESC, X.b, X.c, X.d DESC'], ['a desc', 'X', ' ORDER BY X.a desc'],
			['a desc;T.b asc', 'X', ' ORDER BY X.a desc, T.b asc'],
			['-a;b ASC;c;T.d DESC', 'X', ' ORDER BY X.a DESC, X.b ASC, X.c, T.d DESC'],
			[['-a'], 'X', ' ORDER BY X.a DESC'], [['-a', '-b'], 'X', ' ORDER BY X.a DESC, X.b DESC'],
			[['-a', 'b', 'c', '-d'], 'X', ' ORDER BY X.a DESC, X.b, X.c, X.d DESC'],
			[['P.a DESC'], 'X', ' ORDER BY P.a DESC'], [['a DESC', 'b DESC'], 'X', ' ORDER BY X.a DESC, X.b DESC'],
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
		$this->assertEquals('\'Dasterdly\'', $sql->quoteText($name));
	}

	/**
	 * @param string $expected
	 * @param string $table
	 * @return void
	 * @dataProvider data_quoteTable
	 */
	public function test_quoteTable(string $expected, string $table): void {
		$sql = $this->sql();
		$this->assertEquals($expected, $sql->quoteTable($table));
	}

	public static function data_quoteTable(): array {
		return [
			['`foo`', 'foo', ], ['`Big Table Name space`', 'Big Table Name space', ],
		];
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
			'verb' => 'REPLACE', 'low_priority' => false,
		]));
		$this->assertEquals('REPLACE INTO `table` ( `A` ) VALUES ( \'B\' )', $result);
	}

	public function test_select(): void {
		$sql = $this->sql();

		$where = null;
		$group_by = false;
		$order_by = false;
		$offset = 0;
		$limit = -1;
		$actual = $sql->select([
			'what' => '*', 'tables' => ['thing'], 'where' => $where, 'group_by' => $group_by, 'order_by' => $order_by,
			'offset' => $offset, 'limit' => $limit,
		]);
		$this->assertEquals('SELECT * FROM `thing`', $actual);
	}

	public function test_table_as(): void {
		$sql = $this->sql();

		$table = 'John';
		$as = 'Nancy';
		$this->assertEquals('`John` AS `Nancy`', $sql->table_as($table, $as));
	}

	public function test_unhex(): void {
		$sql = $this->sql();
		$data = 'ABACAB';
		$this->assertEquals('UNHEX(ABACAB)', $sql->function_unhex($data));
	}

	public function test_update(): void {
		$sql = $this->sql();
		$t = 'table';
		$arr = [
			'A' => 2, 'B' => 4,
		];
		$where = [];
		$actual = $sql->update([
			'table' => $t, 'values' => $arr, 'where' => $where, 'low_priority' => true,
		]);
		$actual = self::clean_white($actual);
		$expected = 'UPDATE LOW_PRIORITY `table` SET `A` = 2, `B` = 4';
		$this->assertEquals($expected, $actual);
	}

	public function test_nowUTC(): void {
		$sql = $this->sql();
		$this->assertEquals('UTC_TIMESTAMP()', $sql->nowUTC());
	}

	public function test_where(): void {
		$sql = $this->sql();
		$where = [
			'A' => 'B', 'C' => [
				'D', 'E',
			],
		];
		$conj = 'AND';
		$prefix = '';
		$this->assertEquals('WHERE `A` = \'B\' AND (`C` = \'D\' OR `C` = \'E\')', self::clean_white($sql->where($where, $conj, $prefix)));
	}

	/**
	 * @param string $expected
	 * @param array $array
	 * @param string $conjunction
	 * @param string $prefix_in
	 * @return void
	 * @dataProvider data_whereClause
	 */
	public function test_where_clause(
		string $expected,
		array $array,
		string $conjunction,
		string $prefix_in,
		string $suffix
	): void {
		$sql = $this->sql();
		$this->assertEquals($expected, $sql->where_clause($array, $conjunction, $prefix_in, $suffix));
	}

	public static function data_whereClause(): array {
		return [
			[
				'(`Q`.`Site` = 1 OR `Q`.`Site` = 2 OR `Q`.`Site` = 3) AND Q.UTC<=MIN(F.Stats_UTC)', [
					'Q.Site' => [
						1, 2, 3,
					], '*Q.UTC|<=' => 'MIN(F.Stats_UTC)',
				], '', '', '',
			], [
				'Q.UTC<=MIN(F.Stats_UTC)', [
					'*Q.UTC|<=' => 'MIN(F.Stats_UTC)',
				], '', '', '',
			], [
				'FOO=0', [
					'*FOO' => 0,
				], '', '', '',
			], [
				'FOO IS NULL', [
					'*FOO' => null,
				], '', '', '',
			], [
				'FOO=', [
					'*FOO' => '',
				], '', '', '',
			], [
				'`FOO` = 0', [
					'FOO' => 0,
				], '', '', '',
			], [
				'`FOO` IS NULL <!-- extra -->', [
					'FOO' => null,
				], '', '', '<!-- extra -->',
			], [
				'`FOO` = \'\'', [
					'FOO' => '',
				], '', '', '',
			],

		];
	}
}
