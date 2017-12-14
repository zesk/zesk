<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query.php $
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
	 * 
	 * @var array
	 */
	private $objects_cached = array();
	
	/**
	 * Create a new query
	 *
	 * @param string $type
	 * @param Database $db
	 */
	function __construct($type = "SELECT", Database $db) {
		$this->application = $db->application;
		$this->type = strtoupper($type);
		$this->db = $db;
		$this->dbname = $this->db->code_name();
		$this->class = null;
	}
	
	/**
	 *
	 * @return string[]
	 */
	function __sleep() {
		return array(
			"type",
			"dbname",
			"class"
		);
	}
	
	/**
	 */
	function __wakeup() {
		$this->db = zesk()->application()->database_factory($this->dbname);
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
	function database() {
		return $this->db;
	}
	
	/**
	 *
	 * @return string
	 */
	function database_name() {
		return $this->db->code_name();
	}
	
	/**
	 *
	 * @return Database_SQL
	 */
	function sql() {
		return $this->db->sql();
	}
	/**
	 *
	 * @return Database_Parser
	 */
	function parser() {
		return $this->db->parser();
	}
	/**
	 * Set or get a class associated with this query
	 *
	 * @param string $class
	 * @return Database_Query string
	 */
	function object_class($class = null) {
		if ($class !== null) {
			$this->class = $class;
			return $this;
		}
		return $this->class;
	}
	
	/**
	 *
	 * @return \zesk\Class_ORM
	 */
	function class_object() {
		return $this->db->application->class_object($this->class);
	}
	
	/**
	 * Create objects in the current application context
	 *
	 * @deprecated 2017-12
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Object
	 */
	function object_factory($class, $mixed = null, array $options = array()) {
		$this->application->deprecated();
		return $this->model_factory($class, $mixed, $options);
	}
	
	/**
	 * Create objects in the current application context
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Object
	 */
	function model_factory($class, $mixed = null, array $options = array()) {
		return $this->application->model_factory($class, $mixed, $options);
	}
	
	/**
	 * Cache for Object definitions, do not modify these objects
	 *
	 * @deprecated 2017-12 use orm_registry above
	 * @param string $class
	 * @return \zesk\ORM
	 */
	protected function object_cache($class) {
		zesk()->deprecated();
		return $this->orm_registry($class);
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
