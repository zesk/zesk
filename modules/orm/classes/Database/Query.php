<?php

/**
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
class Database_Query {
    /**
     *
     * @var Application
     */
    public $application = null;

    /**
     * Type of query
     *
     * @var string
     */
    protected $type;

    /**
     * Database
     *
     * @var Database
     */
    protected $db;

    /**
     * Database code
     *
     * @var string
     */
    protected $dbname;

    /**
     * Object class used when iterating
     *
     * @var string
     */
    protected $class;

    /**
     * Inherited class options when linking or iterating
     *
     * @var array
     */
    protected $class_options = array();

    /**
     *
     * @var array
     */
    private $objects_cached = array();

    /**
     *
     * @var Interface_Member_ORM_Factory
     */
    protected $factory = null;

    /**
     * Create a new query
     *
     * @param string $type
     * @param Database $db
     */
    public function __construct($type = "SELECT", Database $db) {
        $this->application = $db->application;
        $this->db = $db;
        $this->type = strtoupper($type);
        $this->dbname = $db->code_name();
        $this->class = null;
        $this->class_options = array();
        $this->factory = $this->application;
    }

    /**
     * Set the object which creates other objects
     *
     * @param Interface_Member_ORM_Factory $factory
     * @return \zesk\Database_Query
     */
    public function set_factory(Interface_Member_Model_Factory $factory) {
        $this->factory = $factory;
        return $this;
    }

    /**
     *
     * @return string[]
     */
    public function __sleep() {
        return array(
            "type",
            "dbname",
            "class",
        );
    }

    /**
     */
    public function __wakeup() {
        // Reconnect upon wakeup
        $this->application = __wakeup_application();
        $this->db = $this->application->database_registry($this->dbname);
    }

    /**
     *
     * @param Database_Query $from
     * @return \zesk\Database_Query
     */
    protected function _copy_from_query(Database_Query $from) {
        $this->type = $from->type;
        $this->db = $from->db;
        $this->dbname = $from->dbname;
        $this->class = $from->class;
        $this->class_options = $from->class_options;
        return $this;
    }

    /**
     *
     * @return Database_Query_Select_Base
     */
    public function duplicate() {
        return clone $this;
    }

    /**
     *
     * @return Database
     */
    public function database() {
        return $this->db;
    }

    /**
     *
     * @return string
     */
    public function database_name() {
        return $this->db->code_name();
    }

    /**
     *
     * @return Database_SQL
     */
    public function sql() {
        return $this->db->sql();
    }

    /**
     *
     * @return Database_Parser
     */
    public function parser() {
        return $this->db->parser();
    }

    /**
     * Set or get a class associated with this query
     *
     * @param string $class
     * @return Database_Query string
     */
    public function orm_class($class = null) {
        if ($class !== null) {
            $this->class = $class;
            return $this;
        }
        return $this->class;
    }

    /**
     * Set or get the class options associated with this query
     *
     * @param array $options
     * @return \zesk\Database_Query|array
     */
    public function orm_class_options(array $options = null) {
        if ($options) {
            $this->class_options = $options;
            return $this;
        }
        return $this->class_options;
    }

    /**
     *
     * @return \zesk\Class_ORM
     */
    public function class_orm() {
        return $this->application->class_orm_registry($this->class);
    }

    /**
     * Create objects in the current application context
     *
     * @param string $class
     * @param mixed $mixed
     * @param array $options
     * @return Object
     */
    public function member_model_factory($member, $class, $mixed = null, array $options = array()) {
        return $this->factory->member_model_factory($member, $class, $mixed, $options);
    }

    /**
     * Cache for Object definitions, do not modify these objects
     *
     * @param string $class
     * @return \zesk\ORM
     */
    protected function orm_registry($class) {
        return $this->application->orm_registry($class);
    }
}
