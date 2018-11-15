<?php
/**
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Database_Query_Insert
 * @author kent
 *
 */
class Database_Query_Insert_Select extends Database_Query_Select {
    /**
     * Low priority insert/replace
     *
     * @var boolean
     */
    protected $low_priority = false;
    
    /**
     *
     * @var string
     */
    private $into = null;
    
    /**
     *
     * @var array
     */
    protected $what = array();
    
    /**
     *
     * @var string
     */
    protected $verb = "INSERT";
    
    /**
     * Create an new query
     *
     * @param string $db
     * @return Database_Query_Insert_Select
     */
    public static function factory(Database $db = null) {
        return new Database_Query_Insert_Select($db);
    }
    
    /**
     *
     * @param Database_Query_Select $query
     * @return \zesk\Database_Query_Insert_Select
     */
    public static function from_database_query_select(Database_Query_Select $query) {
        $new = self::factory($query->database());
        $new->copy_from($query);
        return $new;
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
     * Getter/setter for replace verb
     *
     * @param boolean $replace
     * @return string|\zesk\Database_Query_Insert_Select
     */
    public function replace($replace = null) {
        if ($replace === null) {
            return $this->verb;
        }
        $this->verb = $replace ? "REPLACE" : "INSERT";
        return $this;
    }
    
    /**
     *
     * @param string $table
     * @return \zesk\Database_Query_Insert_Select
     */
    public function into($table) {
        $this->into = $table;
        return $this;
    }

    public function what($mixed = null, $value = null) {
        if (is_string($mixed) && $value === null) {
            throw new Exception_Semantics("Database_Query_Insert_Select must have an associative array for what (passed in \"$mixed\")");
        }
        return parent::what($mixed, $value);
    }

    public function __toString() {
        return $this->sql()->insert_select(array(
            "verb" => $this->verb,
            "table" => $this->into,
            "values" => $this->what,
            "low_priority" => $this->low_priority,
            "select" => parent::__toString(),
        ));
    }

    public function execute() {
        return $this->database()->query($this->__toString());
    }
}
