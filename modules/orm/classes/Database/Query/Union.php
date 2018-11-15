<?php
/**
 * Database Query Union
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query/Union.php $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Union extends Database_Query_Select_Base {
    /**
     * Array of queries to UNION
     *
     * @var array
     */
    protected $queries = array();
    
    /**
     * Order by clause
     *
     * @var string
     */
    private $order_by = null;
    
    /**
     * Construct a new UNION select query
     *
     * @param Database $db
     */
    public function __construct(Database $db) {
        parent::__construct("UNION", $db);
    }
    
    /**
     * Create an new query
     *
     * @param string $db
     * @return Database_Query_Select
     */
    public static function factory($db = null) {
        return new Database_Query_Union($db);
    }

    public function union(Database_Query_Select $select) {
        $this->queries[] = $select;
        return $this;
    }

    public function what($what) {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->what($what);
        }
        return $this;
    }

    public function from($table, $alias = "") {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->from($table, $alias);
        }
        return $this;
    }

    public function join($sql, $join_id = null) {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->join($sql, $join_id);
        }
        return $this;
    }

    public function where($k, $v = null) {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->where($k, $v);
        }
        return $this;
    }

    public function group_by($group_by) {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->group_by($group_by);
        }
        return $this;
    }

    public function order_by($order_by) {
        $this->order_by = $order_by;
        return $this;
    }

    public function limit($offset = 0, $limit = null) {
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $query->limit($offset, $limit);
        }
        return $this;
    }

    public function __toString() {
        $sqls = array();
        foreach ($this->queries as $query) {
            /* @var $query Database_Query_Select */
            $sqls[] = $query->__toString();
        }
        return implode(" UNION ", ArrayTools::wrap($sqls, "(", ")")) . $this->sql()->order_by($this->order_by);
    }
}
