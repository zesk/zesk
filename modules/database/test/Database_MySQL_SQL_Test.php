<?php
namespace zesk;

class Database_MySQL_SQL_Test extends Test_Unit {
    protected $load_modules = array(
        "MySQL",
    );

    public function sql() {
        $db = $this->application->database_registry();
        $sql = $db->sql();
        return $sql;
    }

    public function test_function_date_add() {
        $sqldatetime = null;
        $hours = null;
        $sql = $this->sql();
        $sql->function_date_add($sqldatetime, $hours);
    }

    public function test_delete() {
        $options = array(
            "table" => "test",
            "where" => array(
                "*0" => "1",
            ),
        );
        $sql = $this->sql();
        $sql->delete($options);
    }

    public function test_drop_table() {
        $table = "test";
        $sql = $this->sql();
        $sql->drop_table($table);
    }

    public function test_group_by() {
        $s = null;
        $sql = $this->sql();
        $sql->group_by($s);
    }

    public function test_hex() {
        $sql = $this->sql();
        $data = "data";
        $this->assert_equal($sql->function_hex($data), "HEX(data)");
    }

    public function test_insert() {
        $g = $this->sql();
        $t = "table";
        $arr = array(
            "A" => "B",
        );
        $low_priority = false;
        $sql = $g->insert(array(
            "table" => $t,
            "values" => $arr,
            "low_priority" => $low_priority,
        ));
        $sql = preg_replace('/\s+/', " ", $sql);
        $this->assert_equal($sql, "INSERT INTO `table` ( `A` ) VALUES ( 'B' )", "$sql === \"INSERT INTO `table` ( `A` ) VALUES ( 'B' )\"");
        
        $t = "table";
        $arr = array(
            "Foo" => 1,
            "Why" => 'Du\'de',
        );
        $low_priority = false;
        $sql = $g->insert(array(
            "table" => $t,
            "values" => $arr,
            "low_priority" => $low_priority,
        ));
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql_compare = "INSERT INTO `table` ( `Foo`, `Why` ) VALUES ( 1, 'Du\\'de' )";
        $this->assert_equal($sql, $sql_compare, "$sql === $sql_compare");
        
        echo basename(__FILE__) . ": success\n";
    }

    public function test_now() {
        $sql = $this->sql();
        $sql->now();
        echo basename(__FILE__) . ": success\n";
    }

    public function test_order_by() {
        $sql = $this->sql();
        $s = null;
        $prefix = '';
        $sql->order_by($s, $prefix);
        echo basename(__FILE__) . ": success\n";
    }

    public function test_quote_column() {
        $sql = $this->sql();
        $table = null;
        $sql->quote_column($table);
        echo basename(__FILE__) . ": success\n";
    }

    public function test_quote_text() {
        $sql = $this->sql();
        $name = "Dasterdly";
        $sql->quote_text($name);
    }

    public function test_quote_table() {
        $sql = $this->sql();
        $table = null;
        $this->assert($sql->quote_table("foo") === '`foo`');
        echo basename(__FILE__) . ": success\n";
    }

    public static function clean_white($sql) {
        return trim(preg_replace('/\s+/', " ", $sql));
    }

    public function test_replace() {
        $sql = $this->sql();
        $t = "table";
        $arr = array(
            "A" => "B",
        );
        $low_priority = false;
        $dbname = "";
        $result = preg_replace('/\s+/', " ", $sql->insert(array(
            "table" => $t,
            "values" => $arr,
            "verb" => "REPLACE",
            "low_priority" => $low_priority,
        )));
        $this->assert($result === "REPLACE INTO `table` ( `A` ) VALUES ( 'B' )", "$sql === \"REPLACE INTO `table` ( `A` ) VALUES ( 'B' )\"");
        echo basename(__FILE__) . ": success\n";
    }

    public function test_select() {
        $sql = $this->sql();
        
        $what = null;
        $tables = null;
        $where = null;
        $group_by = false;
        $order_by = false;
        $offset = 0;
        $limit = -1;
        $actual = $sql->select(array(
            "what" => $what,
            "tables" => $tables,
            "where" => $where,
            "group_by" => $group_by,
            "order_by" => $order_by,
            "offset" => $offset,
            "limit" => $limit,
        ));
        echo basename(__FILE__) . ": success\n";
    }

    public function test_table_as() {
        $sql = $this->sql();
        
        $table = "John";
        $as = "Nancy";
        $this->assert_equal("`John` AS `Nancy`", $sql->table_as($table, $as));
    }

    public function test_unhex() {
        $sql = $this->sql();
        $data = "ABACAB";
        $sql->function_unhex($data);
    }

    public function test_update() {
        $sql = $this->sql();
        $t = "table";
        $arr = array(
            "A" => 2,
            "B" => 4,
        );
        $where = array();
        $actual = $sql->update(array(
            "table" => $t,
            "values" => $arr,
            "where" => $where,
            'low_priority' => true,
        ));
        $actual = self::clean_white($actual);
        $expected = "UPDATE LOW_PRIORITY `table` SET `A` = 2, `B` = 4";
        $this->assert_equal($actual, $expected);
    }

    public function test_now_utc() {
        $sql = $this->sql();
        $sql->now_utc();
        echo basename(__FILE__) . ": success\n";
    }

    public function test_where() {
        $sql = $this->sql();
        $where = array(
            "A" => "B",
            "C" => array(
                "D",
                "E",
            ),
        );
        $conj = 'AND';
        $prefix = '';
        $this->assert_equal(self::clean_white($sql->where($where, $conj, $prefix)), "WHERE `A` = 'B' AND (`C` = 'D' OR `C` = 'E')");
    }

    public function test_where_clause() {
        $sql = $this->sql();
        
        $arr = null;
        $conj = "AND";
        $prefix_in = "";
        $sql->where_clause($arr, $conj, $prefix_in);
        
        $where = array(
            "Q.Site" => array(
                1,
                2,
                3,
            ),
            "*Q.UTC|<=" => "MIN(F.Stats_UTC)",
        );
        $this->assert($sql->where_clause($where) === "(`Q`.`Site` = 1 OR `Q`.`Site` = 2 OR `Q`.`Site` = 3) AND Q.UTC<=MIN(F.Stats_UTC)", $sql->where_clause($where));
        
        $this->assert($sql->where_clause(array(
            "*Q.UTC|<=" => "MIN(F.Stats_UTC)",
        )) === "Q.UTC<=MIN(F.Stats_UTC)");
        
        $this->assert($sql->where_clause(array(
            "*FOO" => 0,
        )) === "FOO=0");
        $this->assert($sql->where_clause(array(
            "*FOO" => null,
        )) === "FOO IS NULL");
        $this->assert($sql->where_clause(array(
            "*FOO" => "",
        )) === "FOO=");
        
        $this->assert($sql->where_clause(array(
            "FOO" => 0,
        )) === "`FOO` = 0");
        $this->assert($sql->where_clause(array(
            "FOO" => null,
        )) === "`FOO` IS NULL", $sql->where_clause(array(
            "FOO" => null,
        )));
        $this->assert($sql->where_clause(array(
            "FOO" => "",
        )) === "`FOO` = ''");
    }
}
