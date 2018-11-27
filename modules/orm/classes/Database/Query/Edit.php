<?php
/**
 * Edit
 *
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
abstract class Database_Query_Edit extends Database_Query {
    /**
     * Low priority update/insert
     *
     * @var boolean
     */
    protected $low_priority = false;

    /**
     *
     * @var string
     */
    protected $default_alias = "";

    /**
     * Table we're update/insert
     *
     * @var array
     */
    protected $table = null;

    /**
     * Name => Value of things we're updating/inserting
     *
     * @var array
     */
    protected $values = array();

    /**
     * Array of columns valid for this table
     *
     * @var array
     */
    protected $valid_columns = null;

    /**
     * Get/Set the table for this query
     *
     * @param string $table
     * @return Database_Query_Edit
     */
    public function table($table = null, $alias = null) {
        if ($table === null) {
            return avalue($this->table, "$alias");
        }
        if (count($this->table) === 0) {
            $this->default_alias = $alias;
        }
        $this->table["$alias"] = $table;
        return $this;
    }

    /**
     * Get/Set the table for this query
     *
     * @param string $table
     * @return Database_Query_Edit
     */
    public function class_table($class, $alias = null) {
        $object_class = $this->application->class_orm_registry($class);
        /* @var $object_class Class_ORM */
        if (count($this->table) === 0) {
            $this->default_alias = "$alias";
        }
        $this->table["$alias"] = $object_class->table;
        $this->valid_columns($object_class->column_names(), $alias);
        return $this;
    }

    /**
     * Internal function to check validity of a column
     *
     * @param string $name
     * @return boolean
     */
    private function valid_column($name) {
        $clean_name = ltrim($name, "*");
        list($alias, $clean_name) = pair($clean_name, ".", $this->default_alias, $clean_name);
        $columns = avalue($this->valid_columns, $alias);
        if (!is_array($columns) || !in_array($clean_name, $columns)) {
            return false;
        }
        return true;
    }

    /**
     * Add a name/value pair to be updated in this query
     *
     * @param string $name
     *        	Alternately, pass an array as this value to update multiple values
     * @param mixed $value
     * @return self
     */
    public function value($name, $value = null) {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->value($k, $v);
            }
            return $this;
        }
        if (is_array($this->valid_columns)) {
            $this->check_column($name);
        }
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * Internal function to check a column for vaidity.
     * If not, throw an exception.
     *
     * @param string $name
     * @throws Exception_Semantics
     */
    private function check_column($name) {
        if (!$this->valid_column($name)) {
            throw new Exception_Semantics("Column {name} is not in table {table} (columns are {columns})", array(
                "name" => $name,
                "table" => $this->table,
                "columns" => $this->valid_columns,
                "Database_Query_Edit" => $this,
            ));
        }
    }

    /**
     * Pass multiple values to be inserted/updated
     *
     * @param array $values
     * @return Database_Query_Edit
     */
    public function values(array $values = null) {
        if ($values === null) {
            return $this->values;
        }
        return $this->value($values);
    }

    /**
     * Getter/setter for low priority state of this query
     *
     * @param boolean $low_priority
     * @return boolean Database_Query_Edit
     */
    public function low_priority($low_priority = null) {
        if ($low_priority === null) {
            return $this->low_priority;
        }
        $this->low_priority = to_bool($low_priority);
        return $this;
    }

    /**
     * Not sure if need this.
     * Right now just stores it.
     *
     * @param array $columns
     * @return Database_Query_Insert
     */
    public function valid_columns(array $columns, $alias = null) {
        $this->valid_columns["$alias"] = $columns;
        return $this;
    }
}
