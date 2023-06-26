<?php
declare(strict_types=1);

namespace zesk\MySQL;

use zesk\PHPUnit\TestCase;
use zesk\Database\SQLDialect;
use zesk\Temporal;

class SQLDialectTest extends TestCase {
	protected array $load_modules = [
		'MySQL',
	];

	public function initialize(): void {
		$this->application->configure();
	}

	public function sqlDialect(): SQLDialect {
		return $this->application->databaseRegistry()->sqlDialect();
	}

	public function test_function_date_add(): void {
		$column = 'last_login';
		$hours = $this->randomInteger(1, 20);
		$sql = $this->sqlDialect();
		$this->assertEquals("DATE_ADD(last_login, INTERVAL $hours SECOND)", $sql->functionDateAdd($column, $hours));
		$this->assertEquals("DATE_ADD(last_login, INTERVAL $hours HOUR)", $sql->functionDateAdd($column, $hours, Temporal::UNIT_HOUR));
	}

	public function test_delete(): void {
		$options = [
			'ignore' => true,
		];
		$where = [
			'*0' => '1',
		];
		$sql = $this->sqlDialect();
		$this->assertEquals('DELETE FROM `test` WHERE 0=1', $sql->delete('test', $where, $options));
	}

	public function test_drop_table(): void {
		$table = 'test';
		$sql = $this->sqlDialect();
		$this->assertEquals(['DROP TABLE IF EXISTS `test`'], $sql->dropTable($table));
	}

	public function test_group_by(): void {
		$sql = $this->sqlDialect();
		$this->assertEquals(' GROUP BY dude', $sql->groupBy('dude'));
		$this->assertEquals(' GROUP BY dude, pal', $sql->groupBy(['dude', 'pal']));
	}

	public function test_hex(): void {
		$sql = $this->sqlDialect();
		$data = 'data';
		$this->assertEquals('HEX(data)', $sql->functionHexadecimal($data));
	}

	public function test_insert(): void {
		$g = $this->sqlDialect();
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
		$sql = $this->sqlDialect();
		$this->assertEquals('NOW()', $sql->now());
	}

	public static function data_orderBy(): array {
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
	 * @dataProvider data_orderBy
	 * @return void
	 */
	public function test_orderBy(array|string $order_by, string $prefix, string $expected): void {
		$this->assertEquals($expected, $this->sqlDialect()->orderBy($order_by, $prefix));
	}

	public static function data_quoteColumn(): array {
		return [
			['`user_name`', 'user_name'],
		];
	}

	/**
	 *
	 */
	public function test_quote_column(string $expected, string $column): void {
		$this->assertEquals($expected, $this->sqlDialect()->quoteColumn($column));
	}

	public function test_quote_text(): void {
		$sql = $this->sqlDialect();
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
		$sql = $this->sqlDialect();
		$this->assertEquals($expected, $sql->quoteTable($table));
	}

	public static function data_quoteTable(): array {
		return [
			['`foo`', 'foo', ], ['`Big Table Name space`', 'Big Table Name space', ],
		];
	}

	public static function clean_white(string $sql): string {
		return trim(preg_replace('/\s+/', ' ', $sql));
	}

	public function test_replace(): void {
		$sql = $this->sqlDialect();
		$t = 'table';
		$arr = [
			'A' => 'B',
		];
		$result = preg_replace('/\s+/', ' ', $sql->insert($t, $arr, [
			'verb' => 'REPLACE', 'low_priority' => false,
		]));
		$this->assertEquals('REPLACE INTO `table` ( `A` ) VALUES ( \'B\' )', $result);
	}

	public static function data_select(): array {
		return [
			['SELECT * FROM `thing`', [
				'what' => '*', 'tables' => ['thing'], 'where' => null, 'group_by' => false, 'order_by' => false,
				'offset' => 0, 'limit' => -1,
			]],
			['SELECT * FROM `thing` LIMIT 1,', [
				'what' => '*', 'tables' => ['thing'], 'where' => null, 'group_by' => false, 'order_by' => false,
				'offset' => 1, 'limit' => -1,
			]],
			['SELECT * FROM `thing` GROUP BY MONTH(utc)', [
				'what' => '*', 'tables' => ['thing'], 'where' => null, 'group_by' => ['MONTH(utc)'], 'order_by' =>
					false,
				'offset' => 0, 'limit' => -1,
			]],
		];
	}

	/**
	 * @param string $expected
	 * @param array $selectArguments
	 * @return void
	 * @throws \zesk\Exception\Semantics
	 * @dataProvider data_select
	 */
	public function test_select(string $expected, array $selectArguments): void {
		$sql = $this->sqlDialect();
		$this->assertEquals($expected, $sql->select($selectArguments));
	}

	public function test_table_as(): void {
		$sql = $this->sqlDialect();

		$table = 'John';
		$as = 'Nancy';
		$this->assertEquals('`John` AS `Nancy`', $sql->tableAs($table, $as));
	}

	public function test_unhex(): void {
		$sql = $this->sqlDialect();
		$data = 'ABACAB';
		$this->assertEquals('UNHEX(ABACAB)', $sql->functionDecodeHexadecimal($data));
	}

	public function test_update(): void {
		$sql = $this->sqlDialect();
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
		$sql = $this->sqlDialect();
		$this->assertEquals('UTC_TIMESTAMP()', $sql->nowUTC());
	}

	public function test_where(): void {
		$sql = $this->sqlDialect();
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
		$sql = $this->sqlDialect();
		$this->assertEquals($expected, $sql->whereClause($array, $conjunction, $prefix_in, $suffix));
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
