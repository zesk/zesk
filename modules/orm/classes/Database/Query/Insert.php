<?php
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Insert extends Database_Query_Edit {
    /**
     * This is a REPLACE command
     *
     * @var boolean
     */
    protected $replace = false;

    /**
     *
     * @var Database_Query_Select
     */
    protected $select = null;

    /**
     * Result
     *
     * @var mixed
     */
    protected $result = null;

    /**
     * Construct a new insert query
     *
     * @param Database $db
     */
    public function __construct(Database $db) {
        parent::__construct("INSERT", $db);
    }

    /**
     * Getter/setter for "into" which table
     *
     * @param string $table
     * @return \zesk\Database_Query_Insert|string
     */
    public function into($table = null) {
        if ($table === null) {
            return $this->table;
        }
        $this->table = $table;
        return $this;
    }

    /**
     * Set to replace mode
     *
     * @return Database_Query_Insert
     */
    public function replace($set = null) {
        if (is_bool($set)) {
            $this->replace = $set;
            return $this;
        }
        return $this->replace;
    }

    /**
     * Set to insert mode
     *
     * @return Database_Query_Insert
     */
    public function insert() {
        $this->replace = false;
        return $this;
    }

    /**
     * Insert from a SELECT query
     *
     * @return Database_Query_Insert
     */
    public function select(Database_Query_Select $query) {
        $this->select = $query;
        return $this;
    }

    /**
     * Convert this query to SQL
     *
     * @return string
     */
    public function __toString() {
        $options = array(
            'table' => $this->table,
            'values' => $this->values,
            'low_priority' => $this->low_priority,
        );
        if ($this->replace) {
            $options['verb'] = 'REPLACE';
        }
        if ($this->select) {
            $options['values'] = $this->select->what();
            $options['select'] = strval($this->select);
            return $this->sql()->insert_select($options);
        }
        return $this->sql()->insert($options);
    }

    private function _execute($get_id) {
        if ($this->select) {
            $sql = $this->__toString();
            return $this->database()->query($sql);
        }
        $options = array(
            'table' => $this->table,
            'values' => $this->values,
        );
        if ($this->replace) {
            $options['verb'] = 'REPLACE';
        }
        $this->result = $this->db->insert($this->table, $this->values, $options);
        return $this->result;
    }

    /**
     * Execute the insert and retrieve the ID created
     *
     * @throws Exception_Semantics
     * @return mixed
     */
    public function id() {
        if ($this->low_priority) {
            throw new Exception_Semantics("Can not execute query as low priority and retrieve id: " . $this->__toString());
        }
        if ($this->select) {
            throw new Exception_Semantics("Can not execute query as select and retrieve id: " . $this->__toString());
        }
        return $this->_execute(true);
    }

    /**
     *
     * @return mixed
     */
    public function execute() {
        return $this->_execute(!$this->low_priority);
    }

    /**
     *
     * @return self
     */
    public function exec() {
        $this->_execute(!$this->low_priority);
        return $this;
    }

    /**
     *
     * @return mixed
     */
    public function result() {
        return $this->result;
    }
}
