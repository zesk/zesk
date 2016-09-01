<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/object.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
use zesk\Timestamp as Timestamp;
/**
 * Object provides base class functionality for lists, editing, and creating objects which are
 * generally stored in a database.
 *
 * Subclasses may specify model settings as protected variables as described below, but this method
 * is deprecated in favor of defining a distinct Class_Object subclass to define members and
 * structure.
 *
 * @see Class_Object
 */
abstract class Object extends Model {
	/**
	 * Boolean value which affects Object::is_new() and Object::register() which will not depend
	 * on the auto_column's presence to determine if an Object is new or not.
	 * Will actually check
	 * the database. Allows you to have objects which normally would be created via auto-increment
	 * but instead allows you to create them specifically by ID. Usually used temporarily.
	 *
	 * Do not set this on a global basis via global Object::ignore_auto_column=true as it will
	 * likely have catastrophic negative results on performence.
	 *
	 * @var string
	 */
	const option_ignore_auto_column = "ignore_auto_column";
	
	/**
	 * Previous call resulted in a new object retrieved from the database which exists
	 * 
	 * @see Object::register
	 * @see Object::fetch_if_exists
	 * @var string
	 */
	const status_exists = "exists";
	
	/**
	 * Previous call resulted in the saving of the existing object in the database
	 * 
	 * @see Object::register
	 * @see Object::fetch_if_exists
	 * @var string
	 */
	const status_insert = "insert";
	/**
	 * Previous call failed or has an unknown result
	 * 
	 * @see Object::register
	 * @see Object::fetch_if_exists
	 * @var string
	 */
	const status_unknown = "failed";
	
	/**
	 * Object debugging
	 *
	 * @var boolean
	 */
	static $debug = false;
	
	/**
	 * Global state
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * Initialize this value to an alternate object class name if you want more than one object to
	 * be represented by the same table or class configuration.
	 *
	 * e.g.
	 *
	 * <code>
	 * class Dog extends Cat {
	 * protected $class = "Cat";
	 * }
	 *
	 * @var Class_Object
	 */
	protected $class = null;
	
	/**
	 * The leaf polymorphic class goes here
	 *
	 * @var string
	 */
	protected $polymorphic_leaf = null;
	
	/**
	 * Database name where this object resides.
	 * If not specified, the default database.
	 * <code>
	 * protected $database = "tracker";
	 * </code>
	 *
	 * @var string
	 */
	protected $database_name = null;
	
	/**
	 * Database object
	 * If not specified, the default database.
	 *
	 * @var Database
	 */
	private $database = null;
	
	/**
	 * Database table name
	 * <code>
	 * protected $table = "TArticleComment";
	 * </code>
	 *
	 * @var string
	 */
	protected $table = null;
	
	/**
	 * When is_new requires a database query, cache it here
	 *
	 * @var boolean
	 */
	private $is_new_cached = null;
	
	/**
	 * When storing, set to true to avoid loops
	 *
	 * @var boolean
	 */
	protected $storing = false;
	
	/**
	 * Members of this object
	 *
	 * @var array
	 */
	protected $members = array();
	
	/**
	 * List of things to do when storing
	 *
	 * @var array
	 */
	private $store_queue = array();
	
	/**
	 * Does this object need to be loaded from the database?
	 *
	 * @var boolean
	 */
	private $need_load = true;
	
	/**
	 * Array of columns which I can store
	 */
	private $store_columns;
	
	/**
	 * Result of register call
	 *
	 * @var string
	 */
	private $status = null;
	
	/**
	 * When members is loaded, this is a copy to determine if changes have occurred.
	 *
	 * @var array
	 */
	private $original;
	
	/**
	 * Cache stack
	 *
	 * @var array
	 */
	private $cache_stack = null;
	
	/**
	 * Retrieve user-configurable settings for this object
	 *
	 * @return multitype:multitype:string
	 */
	public static function settings() {
		return array(); //TODO
	}
	/**
	 * Create an object
	 *
	 * @param $class string
	 *        	Object class to create
	 * @param $mixed mixed
	 *        	ID or array to intialize object
	 * @param $options array
	 *        	Additional options for object
	 * @return Object
	 */
	public static function factory($class, $mixed = null, $options = false, Application $application = null) {
		if (!is_string($class)) {
			throw new Exception_Semantics("$class is not a class name");
		}
		if ($mixed instanceof $class) {
			return $mixed;
		}
		$refl_class = new ReflectionClass($class);
		if ($refl_class->isAbstract()) {
			throw new Exception_Semantics("Attempted to create an abstract class \"{class}\"", array(
				"class" => $class
			));
		}
		$object = new $class($mixed, $options, $application);
		if (!$object instanceof Object) {
			throw new Exception_Semantics("{method}({class}) is not a subclass of Object", array(
				"method" => __METHOD__,
				"class" => $class
			));
		}
		return $object->_polymorphic();
	}
	
	/**
	 * Create an object in the context of the current object
	 * 
	 * @param $class string
	 *        	Object class to create
	 * @param $mixed mixed
	 *        	ID or array to intialize object
	 * @param $options array
	 *        	Additional options for object
	 * @return Object
	 */
	public function object_factory($class, $mixed = null, $options = null) {
		return Object::factory($class, $mixed, $options, $this->application);
	}
	
	/**
	 * Create a new object
	 *
	 * @param mixed $mixed
	 *        	Initializing value; either an id or an array of member names => values.
	 * @param array $options
	 *        	List of Options to set before initialization
	 */
	function __construct($mixed = null, $options = false, Application $application = null) {
		parent::__construct(null, $options, $application);
		$this->inherit_global_options();
		$this->initialize_specification();
		$this->members = $this->class->column_defaults;
		$this->initialize($mixed, $this->option('initialize'));
		$this->set_option('initialize', null);
	}
	
	/**
	 * Sleep functionality
	 */
	public function __sleep() {
		return array_merge(array(
			"members"
		), parent::__sleep());
	}
	
	/**
	 * Not sure why we're doing this; perhaps to force cyclical structures from being destroyed,
	 * clean up memory references? KMD
	 */
	public function __destruct() {
		foreach ($this->members as $k => $member) {
			unset($this->members[$k]);
		}
		$this->members = array();
		$this->class = null;
		$this->original = array();
	}
	/**
	 * Wakeup functionality
	 */
	public function __wakeup() {
		$this->application = Application::instance();
		$this->initialize_specification();
		$this->initialize($this->members, 'raw');
	}
	
	/**
	 * Retrieve an option from the class
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public function class_option($name, $default = null) {
		return $this->class->option($name, $default);
	}
	
	/**
	 * Retrieve the Class_Object associated with this object.
	 * Often matches "Class_" . get_class($this), but not always.
	 *
	 * @return Class_Object
	 */
	public function class_object() {
		return $this->class;
	}
	
	/**
	 * All variables for this object (useful for translations, logging, and output)
	 * 
	 * {@inheritDoc}
	 * @see Model::variables()
	 */
	function variables() {
		return $this->members() + arr::kprefix($this->class->variables(), "Class_Object::") + array(
			"Object::class" => get_class($this)
		);
	}
	
	/**
	 *
	 * @param $mixed mixed
	 *        	Model value to retrieve
	 * @param $default mixed
	 *        	Value to return if not found
	 * @return mixed
	 */
	public function get($mixed = null, $default = null) {
		return $this->has($mixed) ? $this->__get($mixed) : $default;
	}
	
	/**
	 *
	 * @param $mixed mixed
	 *        	Model value to set
	 * @param $value mixed
	 *        	Value to set
	 * @return Model $this
	 */
	public function set($mixed, $value = null) {
		if (!is_array($mixed)) {
			$this->__set($mixed, $value);
		} else {
			foreach ($mixed as $k => $v) {
				$this->__set($k, $v);
			}
		}
		return $this;
	}
	
	/**
	 * Retrieve object instance based on class name
	 *
	 * @param $class mixed
	 *        	Class to retrieve instance for (singleton reference), or an object of type Object
	 * @return Object If class not found or instance method does not exist, null is returned.
	 */
	public static function class_instance($class) {
		global $zesk;
		if ($class instanceof Object) {
			return $class;
		}
		$args = func_get_args();
		try {
			$refl = new ReflectionClass($class);
			array_shift($args);
			return $refl->getMethod("instance")->invokeArgs(null, $args);
		} catch (zesk\Exception $e) {
			$zesk->hooks->call("exception", $e);
			log::error($e->raw_message, $e->arguments + array(
				"zesk_exception" => $e
			));
		} catch (Exception $e) {
			$zesk->hooks->call("exception", $e);
			log::error($e->getMessage(), array(
				"exception" => $e
			));
		}
		return null;
	}
	
	/**
	 * Retrieve a blank object.
	 * Useful for retrieving class specification information
	 *
	 * @param $class string
	 *        	Class name to cached
	 * @return Object
	 */
	public static function cached($class) {
		return self::cache_class($class, "object");
	}
	
	/**
	 * Retrieve a list of class dependencies for this object
	 */
	public function dependencies() {
		$result = array();
		foreach ($this->class->has_one as $class) {
			if ($class[0] !== '*') {
				$result['requires'][] = $class;
			}
		}
		foreach (array_keys($this->class->has_many) as $member) {
			$has_many = $this->class->has_many($this, $member);
			$result['requires'][] = $has_many['class'];
			$link_class = avalue($has_many, 'link_class');
			if ($link_class) {
				$result['requires'][] = $link_class;
			}
		}
		
		return $result;
	}
	
	/**
	 * Initialize per-object settings
	 */
	protected function initialize_specification() {
		if (is_string($this->class) && !empty($this->class)) {
			$this->class = Class_Object::instance($this, array(), $this->class);
		}
		if (!$this->class instanceof Class_Object) {
			$this->class = avalue($this->options, 'class_object');
			if (!$this->class instanceof Class_Object) {
				$this->class = Class_Object::instance($this, array(), $this->class);
			} else {
				unset($this->options['class_object']);
			}
		}
		if (!$this->table) {
			$this->table = $this->class->table;
		}
		if (!$this->database_name) {
			$this->database_name = $this->class->database_name;
		}
		$this->store_columns = arr::flip_assign(array_keys($this->class->column_types), true);
		$this->store_queue = array();
		$this->original = array();
	}
	
	/**
	 * Clean a code name to be without spaces or special characters
	 *
	 * @see self::clean_code_name
	 * @param string $name        	
	 */
	static public function clean_code_name($name) {
		$codename = preg_replace('|[\s/]+|', "-", strtolower(trim($name)));
		$codename = preg_replace("/[^-A-Za-z0-9]/", "", $codename);
		return $codename;
	}
	
	/**
	 * Retrieve a cache attached to this object only
	 *
	 * @param $cache_id string
	 *        	A specific cache for this object, or NULL for the global cache fo this object
	 * @return Cache
	 */
	function object_cache($cache_id = null) {
		$name[] = get_class($this);
		$name[] = json::encode($this->id());
		if ($cache_id !== null) {
			$name[] = $cache_id;
		}
		$cache = Cache::register(implode("/", $name));
		if ($this->class->cache_column_names) {
			$cache->invalidate_changed($this, $this->class->cache_column_names);
		} else {
			log::debug("Class {class} does not have cache column names - must invalidate manually", array(
				"class" => get_class($this)
			));
		}
		return $cache;
	}
	
	/**
	 *
	 * @return Database_Schema
	 */
	final public function database_schema() {
		return $this->class->database_schema($this);
	}
	
	/**
	 *
	 * @return Database_Schema
	 */
	function schema() {
		return $this->class->schema($this);
	}
	
	/**
	 * Are the fields in this object determined dynamically?
	 *
	 * @return boolean
	 */
	public function dynamic_columns() {
		return $this->class->dynamic_columns;
	}
	
	/**
	 * Call when the schema of an object has changed and needs to be refreshed
	 */
	public function schema_changed() {
		if ($this->class->dynamic_columns) {
			$this->class->init_columns();
		}
	}
	
	/**
	 * Load a cached version of this class
	 *
	 * @param string $class        	
	 * @param string $component
	 *        	Optional component to return (usually "table", "dbname", "object", "class"
	 * @return Class_Object|mixed
	 */
	static function cache_class($class, $component = "") {
		return Class_Object::cache($class, $component);
	}
	
	/**
	 * Retrieve a cached object instance.
	 * Do not edit, please.
	 *
	 * @param $class string        	
	 * @return Object
	 */
	public static function cache_object($class) {
		return self::cache_class($class, "object");
	}
	
	/**
	 * Cache object data
	 */
	public function cache($key = null, $data = null) {
		if ($key === null) {
			return to_array($this->hook("cache_list"), array());
		} else if ($data === null) {
			return $this->hook_array('cache_load', array(
				$key
			), null);
		} else {
			$this->hook('cache_save', $key, $data);
			return $this;
		}
	}
	
	/**
	 * Cache object data
	 */
	public function cache_dirty($key = null) {
		$this->hook('cache-dirty', $key);
	}
	
	/**
	 * Cache output start, returns "false" if cache hit so do not generate output, e.g.
	 * 
	 * if ($object->cache_output_begin("profile")) {
	 * 		// Generate profile using $object
	 * 		$object->cache_output_end();
	 * }
	 * 
	 * @param mixed $key
	 * @return boolean
	 */
	public function cache_output_begin($key = null) {
		$data = $this->cache($key);
		if ($data) {
			echo $data;
			return false;
		}
		ob_start();
		if ($this->cache_stack === null) {
			$this->cache_stack = array();
		}
		$this->cache_stack[] = $key;
		return true;
	}
	
	/**
	 * End caching, save output to cache
	 * 
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function cache_output_end() {
		if ($this->cache_stack === null || count($this->cache_stack) === 0) {
			throw new Exception_Semantics(get_class($this) . "::cache_output_end before cache_output_begin");
		}
		$content = ob_get_flush();
		$key = array_pop($this->cache_stack);
		return $this->cache($key, $content);
	}
	
	/**
	 * Retrieve the table for an object by class name
	 *
	 * @param $class string        	
	 * @param $mixed mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @param $options mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @return string The table name
	 */
	public static function class_table_name($class, $mixed = null, $options = null) {
		if ($mixed !== null || $options !== null) {
			$object = Object::factory($class, $mixed, $options);
			return $object->table();
		}
		return self::cache_class($class, "table");
	}
	
	/**
	 * Retrieve the id column for an object by class name
	 *
	 * @param $class string        	
	 * @param $mixed mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @param $options mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @return string The table name
	 */
	public static function class_id_column($class) {
		$object = self::cache_class($class, "object");
		return $object->id_column();
	}
	
	/**
	 * Retrieve the id column for an object by class name
	 *
	 * @param $class string        	
	 * @param $mixed mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @param $options mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @return string The table name
	 */
	public static function class_primary_keys($class) {
		$object = self::cache_class($class, "object");
		return $object->primary_keys();
	}
	
	/**
	 * Retrieve the columns for a class
	 *
	 * @param $class string        	
	 * @param $mixed mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @param $options mixed
	 *        	Initialize the object with this (for dynamic tables)
	 * @return string The table name
	 */
	public static function class_table_columns($class, $mixed = null, $options = null) {
		if ($mixed !== null || $options !== null) {
			$object = Object::factory($class, $mixed, $options);
			return $object->columns();
		}
		$object = self::cache_class($class, "object");
		return $object->columns();
	}
	
	/**
	 * Retrieve the database associated with a class
	 *
	 * @param string $class        	
	 */
	public static function class_database($class) {
		/* @var $class Class_Object */
		$class = self::cache_class($class, "class");
		return $class->database();
	}
	
	/**
	 * Retrieve the query object for an object by class name
	 *
	 * @param $class string        	
	 * @return Database_Query_Select
	 * @deprecated 2016-08
	 */
	public static function class_query($class, $alias = null) {
		/* @var $object Object */
		$object = self::cache_class($class, "object");
		return $object->query($alias);
	}
	
	/**
	 * Retrieve the query object for an object by class name
	 *
	 * @param $class string        	
	 * @return Database_Query_Insert
	 * @deprecated 2016-08
	 */
	public static function class_query_insert($class) {
		/* @var $object Object */
		$object = self::cache_class($class, "object");
		return $object->query_insert();
	}
	
	/**
	 * Retrieve the query object for an object by class name
	 *
	 * @param $class string        	
	 * @return Database_Query_Insert_Select
	 * @deprecated 2016-08
	 */
	public static function class_query_insert_select($class) {
		/* @var $object Object */
		$object = self::cache_class($class, "object");
		return $object->query_insert_select();
	}
	
	/**
	 * Retrieve the query object for an object by class name
	 *
	 * @param $class string        	
	 * @return Database_Query_Update
	 * @deprecated 2016-08
	 */
	public static function class_query_update($class, $alias = null) {
		/* @var $object Object */
		$object = self::cache_class($class, "object");
		return $object->query_update($alias);
	}
	
	/**
	 * Retrieve the query object for an object by class name
	 *
	 * @param $class string        	
	 * @return Database_Query_Delete
	 * @deprecated 2016-08
	 */
	public static function class_query_delete($class) {
		/* @var $object Object */
		$object = self::cache_class($class, "object");
		return $object->query_delete();
	}
	
	/**
	 *
	 * @return Database
	 */
	function database(Database $set = null) {
		if ($set !== null) {
			$this->database = $set;
			$this->database_name = $set->code_name();
			return $this;
		}
		if ($this->database instanceof Database) {
			return $this->database;
		}
		return $this->database = $this->application->database_factory($this->database_name);
	}
	
	/**
	 *
	 * @return Database_SQL
	 */
	function sql() {
		return $this->database()->sql();
	}
	
	/**
	 * Determine if a class table exists
	 *
	 * @param $class string        	
	 * @return boolean
	 */
	public static function class_table_exists($class) {
		$cache = self::cache_class($class);
		return $cache['object']->database()->table_exists($cache['table']);
	}
	public final function table() {
		return $this->table ? $this->table : $this->class->table;
	}
	public function table_exists() {
		return $this->database()->table_exists($this->table());
	}
	
	/**
	 * Default implementation of the object name
	 */
	public function name() {
		$name_col = $this->name_column();
		if (empty($name_col)) {
			return null;
		}
		return $this->__get($name_col);
	}
	
	/**
	 * Retrieve the name column for this object (if any)
	 * 
	 * @return string|null
	 */
	public final function name_column() {
		return $this->class->name_column;
	}
	
	/**
	 * Retrieves the single find key for an object, if available. (Multi-key finds always return null)
	 * 
	 * @return string|null
	 */
	public final function find_key() {
		$keys = $this->class->find_keys;
		if (is_array($keys) && count($keys) === 1) {
			return $keys[0];
		}
		return false;
	}
	
	/**
	 * Retrieve list of member names used to find an object in the database
	 * 
	 * @return array:string
	 */
	public final function find_keys() {
		return $this->class->find_keys;
	}
	
	/**
	 * Retrieve list of member names used to find a duplicate object in the database
	 * 
	 * @return array:string
	 */
	public final function duplicate_keys() {
		return $this->class->duplicate_keys;
	}
	
	/**
	 * Returns valid member names for this database table
	 *
	 * Includes dynamic fields including iterators and has_one/has_many/getters/setters
	 *
	 * @return array
	 */
	function member_names() {
		return $this->class->member_names();
	}
	
	/**
	 * Return just database columns for this object
	 *
	 * @return array
	 */
	function columns() {
		return array_keys($this->class->column_types);
	}
	
	/**
	 * Name of this object's class (where is this used?)
	 *
	 * @return string
	 */
	function class_name() {
		return $this->class->name;
	}
	
	/**
	 * If there's an ID column, return the name of the column
	 * 
	 * @return string
	 */
	function id_column() {
		return $this->class->id_column;
	}
	
	/**
	 * Does this object have all primary keys set to a value?
	 * 
	 * @return boolean
	 */
	function has_primary_keys() {
		$pk = $this->class->primary_keys;
		if (count($pk) === 0) {
			return false;
		}
		foreach ($pk as $primary_key) {
			$v = $this->member($primary_key);
			if (empty($v)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * List of primary keys for this object
	 * 
	 * @return array:string
	 */
	function primary_keys() {
		return $this->class->primary_keys;
	}
	
	/**
	 * Class code name
	 * 
	 * @return string
	 */
	function class_code_name() {
		return $this->class->code_name;
	}
	
	/**
	 * Always use UTC timestamps when setting dates for this object
	 * 
	 * @return boolean
	 */
	function utc_timestamps() {
		return $this->class->utc_timestamps;
	}
	
	/**
	 * Select the current database if needed
	 */
	function select_database() {
		$db = $this->database();
		if (!$db) {
			return null;
		}
		return $db->select_database();
	}
	
	/**
	 * Ensure this object is loaded from database if needed
	 */
	function refresh() {
		if ($this->need_load && $this->can_fetch()) {
			$this->fetch();
		}
		$this->need_load = false;
	}
	
	/**
	 * Object initialization; when creating an object this should be called using two methods: An
	 * integer ID for this object, or an array of populated values, or from the database itself
	 *
	 * @param $mixed mixed        	
	 * @return Object
	 */
	function initialize($mixed, $initialize = false) {
		$this->is_new_cached = null;
		if (is_array($mixed)) {
			$this->_inited = count($mixed) !== 0;
			if ($initialize === true) { // Means from database
				$mixed = $this->class->from_database($this, $mixed);
				$this->is_new_cached = false;
			} else if ($initialize === 'raw') {
				// Nothing.
			} else {
				$mixed = $this->class->from_array($this, $mixed);
			}
			$this->original = $this->to_database($mixed);
			$this->members = $mixed + $this->members;
			$this->need_load = false;
		} else if ($mixed !== null) {
			if ($this->class->id_column !== null) {
				$this->members[$this->class->id_column] = $mixed;
				$this->_inited = true;
				$this->original = array();
				$this->need_load = true;
			} else {
				throw new Exception_Semantics(get_class($this) . " initialized with single value but no id column");
			}
		} else {
			$this->_inited = false;
			$this->members = $this->class->column_defaults;
			$this->original = array();
			$this->need_load = true;
		}
		$this->store_queue = array();
		$this->hook("initialized");
		return $this;
	}
	
	/**
	 * Is this a new object, or not?
	 *
	 * @return boolean
	 */
	function is_new($set = null) {
		if ($set !== null) {
			$this->is_new_cached = to_bool($set);
			return $this;
		}
		if (is_bool($this->is_new_cached)) {
			return $this->is_new_cached;
		}
		$auto_column = $this->class->auto_column;
		if ($auto_column && !$this->option_bool(self::option_ignore_auto_column)) {
			$auto = $this->member($auto_column);
			return empty($auto);
		} else if (count($pk = $this->class->primary_keys) > 0) {
			if ($this->member_is_empty($pk)) {
				return true;
			}
			$where = $this->members($pk);
			$sql = $this->sql()->select(array(
				'what' => array(
					'*X' => 'COUNT(*)'
				),
				'tables' => $this->table(),
				'where' => $where
			));
			
			$this->is_new_cached = !to_bool($this->database()->query_integer($sql, "X"));
			return $this->is_new_cached;
		}
		return true; // Always new
	}
	
	/**
	 * Empty out this object's members and set to defaults
	 * 
	 * @return Object
	 */
	function clear() {
		$this->members = $this->class->column_defaults;
		$this->store_queue = array();
		return $this;
	}
	
	/**
	 * Ouptut the display name for this object.
	 * 
	 * @return string
	 */
	function display_name() {
		$name_column = $this->class->name_column;
		if (!$name_column) {
			return "";
		}
		return $this->member($name_column);
	}
	
	/**
	 * Get/set the ID for this object
	 * 
	 * @param mixed $set
	 * @return Object|mixed
	 */
	function id($set = null) {
		if (!$this->class) {
			log::fatal("Calling {method} on uninitialized Object {class} {backtrace}", array(
				"method" => __METHOD__,
				"class" => get_class($this),
				"backtrace" => _backtrace()
			));
		}
		/**
		 * Single ID
		 */
		if (is_string($idcol = $this->class->id_column)) {
			if ($set !== null) {
				return $this->set($idcol, $set);
			}
			$id = avalue($this->members, $idcol);
			if ($id instanceof Object) {
				return $id->id();
			}
			if (!array_key_exists($idcol, $this->class->column_types)) {
				throw new Exception_Semantics("Class {class} does not define {idcol} in column types {column_types}", array(
					'class' => get_class($this),
					'idcol' => $idcol,
					'column_types' => $this->class->column_types
				));
			}
			$type = $this->class->column_types[$idcol];
			return $type === Class_Object::type_id || $type === Class_Object::type_integer ? intval($id) : strval($id);
		}
		/**
		 * No ID columns
		 */
		if (count($pk = $this->class->primary_keys) === 0) {
			return null;
		}
		/**
		 * Multiple ID columns
		 */
		if ($set === null) {
			return $this->members($pk);
		}
		
		/**
		 * Passing a string or list of values to load
		 */
		if (is_string($set) || arr::is_list($set)) {
			$ids = to_list($set);
			if (count($ids) !== count($pk)) {
				log::warning("{class}::id(\"{set}\") mismatches primary keys (expected {npk})", array(
					"class" => get_class($this),
					"set" => $set,
					"npk" => count($pk)
				));
			}
			foreach ($pk as $index => $k) {
				$this->members[$k] = avalue($ids, $index);
			}
			return $this;
		}
		/**
		 * Passing an array of primary keys (hopefully)
		 */
		if (is_array($set)) {
			$missing = array();
			foreach ($pk as $k) {
				if (array_key_exists($k, $set)) {
					$this->set($k, $set);
				} else {
					$missing[] = $k;
				}
			}
			if (count($missing) > 0) {
				log::warning("{class}::id(\"{set}\") missing primary keys: {k}", array(
					"class" => get_class($this),
					"set" => json::encode($set),
					"ks" => implode(",", $missing)
				));
			}
			$this->set($set);
			return $this;
		}
		
		throw new Exception_Semantics("{class}::id(\"{value}\" {type}) unknown parameter: ", array(
			"class" => get_class($this),
			"value" => _dump($set),
			"type" => type($set)
		));
	}
	
	/**
	 * Returns name of the database used by this object
	 *
	 * @return string
	 * @see Object::database_name()
	 */
	function database_name() {
		return $this->database_name;
	}
	
	/**
	 * Retrieve a query for the current object
	 *
	 * @param $alias string        	
	 * @return Database_Query_Select
	 */
	function query($alias = null) {
		$query = new Database_Query_Select($db = $this->database());
		$query->object_class(get_class($this));
		if (empty($alias)) {
			$alias = "X";
		}
		return $query->from($this->table(), $alias)->what(null, $db->sql()->column_alias("*", $alias));
	}
	
	/**
	 * Create an insert query for this object
	 *
	 * @return Database_Query_Insert
	 */
	function query_insert() {
		$query = new Database_Query_Insert($this->database());
		$query->object_class(get_class($this));
		return $query->into($this->table())->valid_columns($this->columns());
	}
	
	/**
	 * Create an insert -> select query for this object
	 *
	 * @return Database_Query_Insert
	 */
	function query_insert_select($alias = "") {
		$query = new Database_Query_Insert_Select($this->database());
		$query->object_class(get_class($this));
		$query->from($this->table(), $alias);
		return $query->into($this->table());
	}
	
	/**
	 * Create an update query for this object
	 *
	 * @return Database_Query_Update
	 */
	function query_update($alias = null) {
		$query = new Database_Query_Update($this->database());
		return $query->object_class(get_class($this))->table($this->table(), $alias)->valid_columns($this->columns(), $alias);
	}
	
	/**
	 * Create an delete query for this object
	 *
	 * @return Database_Query_Delete
	 */
	function query_delete() {
		$db = $this->database();
		$query = new Database_Query_Delete($db);
		$query->object_class(get_class($this));
		return $query;
	}
	
	/**
	 * Retrieve an iterator for the current object
	 *
	 * @param $alias string        	
	 * @return Object_Iterator
	 */
	function iterator(Database_Query_Select $query, $options = null) {
		if (!is_array($options)) {
			$options = array();
		}
		$class = aevalue($options, "iterator_class", "Object_Iterator");
		$iterator = new $class(get_class($this), $query, $this->inherit_options() + $options);
		return $iterator;
	}
	
	/**
	 * Iterate on an object's member
	 *
	 * @param $member string
	 *        	Many member
	 * @param $where mixed
	 *        	Optional where query
	 * @return Object_Iterator
	 */
	protected function member_iterator($member, $where = null) {
		$has_many = $this->class->has_many($this, $member);
		if ($has_many === null) {
			throw new Exception_Semantics(__CLASS__ . "::member_iterator($member) called on non-many member");
		}
		if ($this->is_new()) {
			return to_array(avalue($this->members, $member, array()));
		}
		$object = null;
		$query = $this->member_query($member, $object);
		if ($where) {
			$query->where(arr::kprefix($where, $query->alias() . "."));
		}
		/*
		 * @var $object Object
		 */
		$iterator = $object->iterator($query, array(
			"class" => avalue($has_many, "iterator_class")
		) + $this->inherit_options());
		if (!avalue($has_many, 'table')) {
			$iterator->set_parent($this, $has_many['foreign_key']);
		}
		return $iterator;
	}
	
	/**
	 * Create a query for an object's member
	 *
	 * @param $member string
	 *        	Many member
	 * @param $object Object
	 *        	Object related to this member, optionally returned
	 * @return Database_Query_Select
	 */
	public function member_query($member, &$object = null) {
		return $this->class->member_query($this, $member, $object);
	}
	
	/**
	 * Create a query for an object's member
	 *
	 * @param $member string
	 *        	Many member
	 * @param $object Object
	 *        	Object related to this member, optionally returned
	 * @return Database_Query_Select
	 * @todo Unimplemented
	 */
	public function member_query_update($member, &$object = null) {
		return $this->class->member_query_update($this, $member, $object);
	}
	
	/**
	 *
	 * @param unknown $member        	
	 */
	private function member_foreign_list($member) {
		if ($this->is_new()) {
			return array_keys(to_array(avalue($this->members, $member, array())));
		}
		return $this->class->member_foreign_list($this, $member);
	}
	private function member_foreign_exists($member, $id) {
		if ($this->is_new()) {
			return arr::path($this->members, array(
				$member,
				$id
			)) !== null;
		}
		return $this->class->member_foreign_exists($this, $member, $id);
	}
	private function member_foreign_delete($member) {
		$queue = $this->class->member_foreign_delete($this, $member);
		if (is_array($queue)) {
			$this->store_queue += $queue;
		}
		//		if ($this->is_new()) {
		$this->members[$member] = array();
		//		}
	}
	private function member_foreign_add($member, Object $object) {
		//if ($this->is_new()) {
		$this->members[$member][$object->id()] = $object;
		//}
		$this->store_queue += $this->class->member_foreign_add($this, $member, $object);
	}
	private function _fk_delete($table, $foreign_key) {
		$sql = $this->sql()->delete(array(
			'table' => $table,
			'where' => array(
				$foreign_key => $this->id()
			)
		));
		$this->database()->query($sql);
	}
	private function _fk_store(Object $object, $update_key) {
		$object->$update_key = $this->id();
		return $object->store();
	}
	private function _fk_link_store(Object $object, $table, $replace) {
		if ($object->is_new() || $object->changed()) {
			$object->store();
		}
		$map = array(
			'Foreign' => $this->id(),
			'Far' => $object->id()
		);
		return $this->database()->replace($table, map($replace, $map));
	}
	public function inherit_options() {
		if ($this->class->inherit_options) {
			return $this->options_include($this->class->inherit_options);
		}
		// We do not want to inherit options by default. For example "class_object" is an option which affects polymorphic objects, which
		// should not be inherited.
		// KMD: Removed this as it inherits "database" which is wrong. Err on the side of no inheritance unless class specifies it.
		// return $this->options_exclude("class_object");
		return array();
	}
	
	/**
	 * Retrieve the original value of an object's member prior to modifying in memory and before storing
	 * 
	 * @param string $member
	 * @param mixed $default
	 * @return mixed
	 */
	protected function original($member = null, $default = null) {
		if ($member === null) {
			return $this->original;
		}
		$save = $this->members;
		$this->members = $this->original;
		$result = $this->get($member, $default);
		$this->members = $save;
		return $result;
	}
	
	/**
	 * Whenever an object attached to this object is requested, this method is called.
	 * 
	 * Override in subclasses to get special behavior.
	 *
	 * @param string $member
	 *        	Name of the member we are fetching
	 *        	
	 * @param string $class
	 *        	Class of member
	 * @param string $data
	 *        	Current data stored in member
	 * @param array $options
	 *        	Options to create when creating object
	 * @return Object|null
	 */
	protected function member_object_factory($member, $class, $data, $options = false) {
		return $this->object_factory($class, $data, $options)->fetch();
	}
	
	/**
	 * Retrieve a member which is another Object
	 * 
	 * @param string $member
	 * @param mixed $options
	 */
	final protected function member_object($member, array $options = array()) {
		$this->refresh();
		$data = aevalue($this->members, $member);
		if (!$data) {
			return null;
		}
		if (!array_key_exists($member, $this->class->has_one)) {
			throw new Exception_Semantics("Accessing {class}::member_object but {member} is not in has_one", array(
				"class" => get_class($this),
				"member" => $member
			));
		}
		$class = $this->class->has_one[$member];
		if ($class[0] === '*') {
			$class = $this->member(substr($class, 1));
		}
		if ($data instanceof $class) {
			return $data;
		}
		if ($data === null || $data === '') {
			return null;
		}
		try {
			$object = $this->member_object_factory($member, $class, $data, $options + $this->inherit_options());
		} catch (Exception_Object_NotFound $e) {
			if ($this->option_bool("fix_member_objects")) {
				global $zesk;
				$zesk->hooks->call("exception", $e);
				log::error("Fixing not found {member} {member_class} (#{data}) in {class} (#{id})", array(
					"member" => $member,
					"member_class" => $class,
					"data" => $data,
					"class" => get_class($this),
					"id" => $this->id()
				));
				$this->members[$member] = null;
				// TODO - add option to store?
				return null;
			} else {
				throw $e;
			}
		}
		if ($object) {
			$this->members[$member] = $object;
			return $object;
		}
		return null;
	}
	
	/**
	 * Does this object have a member value?
	 * 
	 * {@inheritDoc}
	 * @see Model::has()
	 */
	function has($member = null) {
		// Need to check $this->members to handle listing an object with additional query fields which may not be configured in the base object
		// Prevents ->defaults() from nulling the value if it's in there
		return $this->has_member($member) || array_key_exists($member, $this->members);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Model::__get()
	 */
	function __get($member) {
		if (($method = avalue($this->class->getters, $member)) !== null) {
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("Object getter \"$method\" for " . get_class($this) . " does not exist");
			}
			return call_user_func_array(array(
				$this,
				$method
			), array(
				$member
			));
		}
		if (array_key_exists($member, $this->class->has_many)) {
			if (array_key_exists($member, $this->members)) {
				return $this->members[$member];
			}
			$many = $this->class->has_many[$member];
			return $this->member_iterator($member, avalue($many, 'iterator_where'));
		}
		if (array_key_exists($member, $this->class->has_one)) {
			return $this->member_object($member, $this->inherit_options());
		}
		return $this->member($member);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Model::__unset()
	 */
	function __unset($member) {
		if (array_key_exists($member, $this->class->has_many)) {
			$this->member_foreign_delete($member);
			$this->members[$member] = array();
			return;
		}
		$this->set_member($member, null);
	}
	
	/**
	 * 
	 * @param unknown $value
	 */
	function member_find($value) {
		if (is_string($value)) {
			$find_keys = $this->class->find_keys;
			if (count($find_keys) === 1) {
				$value = array(
					$find_keys[0] => $value
				);
			} else {
				return false;
			}
		}
		if (is_array($value)) {
			$this->set_member($value);
			if ($this->find()) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Model::__isset()
	 */
	public function __isset($member) {
		return !$this->member_is_empty($member);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Model::__set()
	 */
	function __set($member, $value) {
		if (($method = avalue($this->class->setters, $member)) !== null) {
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("Object setter \"$method\" for " . get_class($this) . " does not exist");
			}
			return call_user_func_array(array(
				$this,
				$method
			), array(
				$value,
				$member, /* Allow simple case to be written more easily */
			));
		}
		if (array_key_exists($member, $this->class->has_many)) {
			if (is_array($value)) {
				$this->__unset($member);
				foreach ($value as $v) {
					$this->__set($member, $v);
				}
				return;
			}
			if (!$value instanceof Object) {
				if ($value === null) {
					$this->member_foreign_delete($member);
					return;
				}
				$value = $this->object_factory($this->class->has_many[$member]['class'], $value);
			}
			$this->member_foreign_add($member, $value);
			return;
		}
		if (array_key_exists($member, $this->class->has_one)) {
			$class = $this->class->has_one[$member];
			$dynamic_member = $class[0] === '*' ? substr($class, 1) : null;
			if ($value instanceof Object) {
				if ($dynamic_member) {
					$this->set_member($dynamic_member, get_class($value));
				}
			} else if ($value !== null) {
				if ($dynamic_member) {
					$class = $this->member($dynamic_member);
					if (empty($class)) {
						throw new Exception_Semantics("Must set member {member} with class before using non-Object __set on class {class} with value {value}", array(
							'member' => $dynamic_member,
							'class' => get_class($this),
							'value' => $value
						));
					}
				}
				$object = $this->object_factory($class);
				if ($object->member_find($value)) {
					$this->set_member($member, $object);
					return;
				}
			}
		}
		$this->set_member($member, $value);
		$this->_inited = true;
	}
	
	/**
	 * 
	 * @param unknown $member
	 */
	function links($member) {
		if (array_key_exists($member, $this->class->has_many)) {
			return $this->member_foreign_list($member);
		}
		return null;
	}
	
	/**
	 * 
	 * @param unknown $member
	 * @param unknown $value
	 */
	function is_linked($member, $value) {
		if (array_key_exists($member, $this->class->has_many)) {
			return $this->member_foreign_exists($member, $value);
		}
		return false;
	}
	
	/**
	 * Retrieve a member as a boolean value
	 *
	 * @param $member string
	 *        	Name of member
	 * @param $def mixed
	 *        	Default value to return if can't convert to boolean
	 * @return integer
	 */
	function member_boolean($member, $def = null) {
		$this->refresh();
		return to_bool(avalue($this->members, $member), $def);
	}
	
	/**
	 * Retrieve a member as a timestamp value
	 *
	 * @param $member string
	 *        	Name of member
	 * @param $def mixed
	 *        	Use this value if member does not exist
	 * @return Timestamp
	 */
	function member_timestamp($member, $def = null) {
		$this->refresh();
		$value = avalue($this->members, $member);
		if (!$value) {
			return $def;
		}
		return Timestamp::factory($value);
	}
	
	/**
	 * Retrieve a member as an integer
	 *
	 * @param $member string
	 *        	Name of member
	 * @param $def mixed
	 *        	Default value to return if can't convert to integer
	 * @return integer
	 */
	function member_integer($member, $def = null) {
		$this->refresh();
		$result = avalue($this->members, $member, $def);
		if (is_numeric($result)) {
			return intval($result);
		}
		if ($result instanceof Object) {
			return $result->id();
		}
		return $def;
	}
	
	/**
	 * Retrieve a member of this object
	 *
	 * @param $member string
	 *        	Field to retrieve
	 * @param $def mixed
	 *        	Default value to return if field doesn't exist
	 * @return mixed
	 */
	function member($member, $def = null) {
		$this->refresh();
		return avalue($this->members, $member, $def);
	}
	
	/**
	 * Getter/setter for serialized array attached to an object
	 *
	 * @param string $member        	
	 * @param string $mixed        	
	 * @param string $value        	
	 * @return Object|mixed
	 */
	public function member_data($member, $mixed = null, $value = null) {
		$data = to_array($this->member($member));
		if (is_array($mixed)) {
			// $value in this context means "append" or not (boolean)
			$this->set_member($member, $value ? $mixed + $data : $mixed);
			return $this;
		} else if (is_string($mixed)) {
			if ($value === null) {
				// Value null in this context means ->get($mixed)
				return avalue($data, $mixed);
			} else {
				// Value non-null  in this context means ->set($mixed, $value)
				$data[$mixed] = $value;
				$this->set_member($member, $data);
				return $this;
			}
		}
		return $this->member($member);
	}
	
	/**
	 * Have any of the members given changed in this object?
	 *
	 * @param mixed $members
	 *        	Array or list of members
	 * @return boolean
	 */
	function members_changed($members) {
		$members = to_list($members);
		$data = $this->members($members);
		$column_types = $this->class->column_types;
		foreach ($members as $column) {
			if (array_key_exists($column, $column_types)) {
				$this->class->member_to_database($this, $column, $column_types[$column], $data);
			}
			if (avalue($this->original, $column) !== avalue($data, $column)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Did anything change in this object? If no parameters are passed, determines if any
	 * database member has changed.
	 *
	 * Does not include changes to Object members other than ID changes.
	 *
	 * @param list $members
	 *        	List of members to test for changes
	 * @return boolean
	 */
	function changed($members = null) {
		return $this->members_changed($members === null ? $this->columns() : $members);
	}
	
	/**
	 * Retrieve the changes to this object as an array of member => array("old value", "new value")
	 *
	 * @return array
	 */
	function changes() {
		$changes = array();
		foreach ($this->columns() as $k) {
			if ($this->members_changed($k)) {
				$changes[$k] = array(
					avalue($this->original, $k),
					avalue($this->members, $k)
				);
			}
		}
		return $changes;
	}
	function membere($member, $def = null) {
		$this->refresh();
		return aevalue($this->members, $member, $def);
	}
	function &members($mixed = false) {
		$this->refresh();
		if (is_string($mixed)) {
			$mixed = explode(";", $mixed);
		}
		if (!is_array($mixed)) {
			return $this->members;
		}
		$temp_data = arr::filter($this->members, $mixed);
		return $temp_data;
	}
	
	/**
	 * Returns true if the member is empty
	 * For multiple members, returns true if ANY member is empty
	 * For multiple members, returns false if no members are passed in
	 *
	 * @param mixed $member        	
	 * @return boolean
	 */
	function member_is_empty($member) {
		if (is_array($member)) {
			foreach ($member as $m) {
				if ($this->member_is_empty($m)) {
					return true;
				}
			}
			return false;
		}
		$d = $this->member($member, null);
		return empty($d);
	}
	
	/**
	 * Complex setter
	 *
	 * Delete a value from the data:
	 *
	 * $object->set_member_serial("data", "name", null);
	 *
	 * Set a value to the data (must serialize properly within Zesk)
	 *
	 * $object->set_member_serial("data", "name", $value);
	 *
	 * Overwrite multiple data elemets:
	 *
	 * $object->set_member_serial("data", array("name1" => $value1, "name2" => $value2));
	 *
	 * Set multiple data elemets only if they are not set already:
	 *
	 * $object->set_member_serial("data", array("name1" => $value1, "name2" => $value2), false);
	 *
	 * @param string $member        	
	 * @param mixed $mixed        	
	 * @param mixed $value        	
	 */
	public function set_member_serial($member, $mixed = null, $value = null) {
		assert(array_key_exists($member, $this->class->members_of_type(Class_Object::type_serialize)));
		$data = to_array($this->__get($member));
		if (is_string($mixed) || is_numeric($mixed)) {
			if ($value === null) {
				unset($data[$mixed]);
			} else {
				$data[$mixed] = $value;
			}
		} else if (is_array($mixed)) {
			$overwrite = to_bool($value, true); // $value === null -> true
			$data = $overwrite ? $mixed + to_array($data) : to_array($data) + $mixed;
		} else {
			throw new Exception_Parameter("\$mixed is of type {type} - unsupported ({mixed})", array(
				"type" => type($mixed),
				"mixed" => $mixed
			));
		}
		if (count($data) === 0) {
			$data = null;
		}
		$this->__set($member, $data);
		return $this;
	}
	
	/**
	 * Set a member to a value
	 *
	 * @param string $member        	
	 * @param mixed $v        	
	 * @param boolean $overwrite        	
	 * @return $this
	 */
	function set_member($member, $v = null, $overwrite = true) {
		$this->refresh();
		if (is_array($member)) {
			foreach ($member as $k => $v) {
				$this->set_member($k, $v, $overwrite);
			}
		} else if ($overwrite || !isset($this->members[$member])) {
			if ($member === $this->class->auto_column || in_array($member, $this->class->primary_keys)) {
				$this->is_new_cached = null;
			}
			$this->members[$member] = $v;
		}
		return $this;
	}
	function member_remove($member) {
		$member = to_list($member);
		if (!is_array($member)) {
			backtrace();
		}
		foreach ($member as $m) {
			unset($this->members[$m]);
		}
	}
	
	/**
	 * Change the status of the store column structure
	 *
	 * @param string $member        	
	 * @param null|boolean $store        	
	 */
	private function _store_member($member, $store = null) {
		if (!array_key_exists($member, $this->store_columns)) {
			log::warning("Unknown column {member} in object {object} ({store_columns})", array(
				"member" => $member,
				"object" => $this,
				"store_columns" => array_keys($this->store_columns)
			));
			return $store === null ? null : $this;
		} else {
			if ($store === null) {
				return avalue($this->store_columns, $member);
			}
			$this->store_columns[$member] = to_bool($store);
			return $this;
		}
	}
	private function _filter_store_members(array $members) {
		foreach ($members as $member => $value) {
			if (!avalue($this->store_columns, $member)) {
				unset($members[$member]);
			}
		}
		return $members;
	}
	/**
	 * Enable or disable the storing of particular members of this object.
	 *
	 * You can do:
	 *
	 * $this->store_member("a;b;c") to retrieve the values back as array("a" => true, "b" => false,
	 * "c" => true)
	 *
	 * or
	 *
	 * $this->store_member("a;b;c", true) to set members a,b,c to be stored.
	 *
	 * @param mixed $member
	 *        	member name, list of member names, or array of member names
	 * @param boolean|null $store
	 *        	true or false to set the value, null to retrieve the values
	 */
	function store_member($member = null, $store = null) {
		if ($member === null) {
			return $this->store_columns;
		}
		$members = to_list($member);
		if ($store === null) {
			$result = array();
			foreach ($members as $m) {
				$result[$m] = $this->_store_member($m);
			}
			return count($members) === 1 && is_string($member) ? $result[$member] : $result;
		}
		foreach ($members as $m) {
			$this->_store_member($m, $store);
		}
		return $this;
	}
	
	/**
	 * Does this object define the member given?
	 *
	 * @todo concept of member here differs from other senses? OK? Not OK?
	 * @see Object::member_empty
	 * @param string $member        	
	 * @return boolean
	 */
	function has_member($member) {
		return array_key_exists($member, $this->class->column_types) || array_key_exists($member, $this->class->has_many) || array_key_exists($member, $this->class->has_one);
	}
	protected function default_duplicate_rename_patterns() {
		$patterns = array();
		$patterns[] = "";
		$limit = min($this->option("dupliate_rename_limit", 100), 1000);
		for ($i = 1; $i < $limit; $i++) {
			$patterns[] = " $i";
		}
		return $patterns;
	}
	
	/**
	 * Rename a copy
	 *
	 * @param unknown_type $base_name        	
	 */
	protected function duplicate_rename($column, Database_Query_Select $select, $rename_pattern = null) {
		$name = $this->get($column);
		$class = get_class($this);
		if ($rename_pattern === null) {
			$rename_pattern = $this->option("duplicate_rename", __("$class:={0} (Copy{1})"));
		}
		// Quote all characters but {} which are used in the map call
		$preg_pattern = '#^' . map(strtr(preg_quote($rename_pattern, "#"), array(
			"\\{" => "{",
			"\\}" => "}"
		)), array(
			"(.*)",
			"([ 0-9]*)"
		)) . '$#';
		$matches = null;
		// If pattern found, pull out new base name (e.g. "Foo (Copy 2)" => "Foo"
		$base_name = preg_match($preg_pattern, $name, $matches) ? $matches[1] : $name;
		// Gather patterns to be used for new names (must include spacing if needed
		$patterns = $this->hook_array("duplicate_rename_patterns", array(), null);
		if (!is_array($patterns)) {
			$patterns = $this->default_duplicate_rename_patterns();
		}
		foreach ($patterns as $pattern) {
			// Generate a new name
			$test_name = trim(map($rename_pattern, array(
				$base_name,
				$pattern
			)));
			$select->what("*X", "COUNT(DISTINCT $column)");
			$select->where($column, $test_name);
			if ($select->integer("X") === 0) {
				// If it doesn't exist, then we're done
				$this->set($column, $test_name);
				return $this;
			}
		}
		return null;
	}
	protected function duplicate(Options_Duplicate &$options = null) {
		if ($options === null) {
			$options = new Options_Duplicate($this->inherit_options());
		}
		$member_names = arr::remove_values(array_keys($this->class->column_types), $this->class->id_column);
		log::debug("member_names={names}", array(
			"names" => $member_names
		));
		$new_object = $this->object_factory(get_class($this), $this->members($member_names), array_merge($this->inherit_options(), $options->option()));
		$options->process_duplicate($new_object);
		return $new_object;
	}
	protected function inherit_members(Object $obj, $members = "*") {
		if ($members === "*") {
			$members = $obj->members();
		} else {
			$members = to_list($members);
		}
		if (!is_array($members)) {
			return false;
		}
		foreach ($members as $member) {
			$this->set_member($member, $obj->member($member));
		}
		return true;
	}
	private function _sqlNow() {
		$generator = $this->sql();
		return $this->utc_timestamps() ? $generator->now_utc() : $generator->now();
	}
	
	/*
	 * Insert SQL
	 */
	public function insert_sql() {
		$member = $this->pre_insert();
		return $this->database()->insert($this->table(), $member);
	}
	
	/**
	 * Prepare the internal data structure for output to the database
	 *
	 * Calls
	 *
	 * $this->hook_insert_alter(array $data)
	 * Object::insert_alter(Object $object, array $data)
	 *
	 * Note final data structure will be trimed down to values which exist in $this->store_columns
	 *
	 * @return array
	 */
	protected function pre_insert() {
		$members = $this->hook_array("pre_insert", array(
			$this->members
		), $this->members);
		$members = $this->_filter_store_members($members);
		$this->select_database();
		return $this->to_database($members, true);
	}
	private function insert() {
		if ($this->option_bool("disable_database") || $this->option_bool("disable_database_insert")) {
			return false;
		}
		$members = $this->pre_insert();
		if (count($members) === 0) {
			throw new Exception_Object_Empty(get_class($this), "{class}: All members: {members} Store members: {store}", array(
				"members" => array_keys($this->members),
				"store" => $this->store_columns
			));
		}
		try {
			if ($this->class->auto_column) {
				$auto_id = $this->database()->insert($this->table(), $members);
				if (is_numeric($auto_id)) {
					$this->set_member($this->class->auto_column, $auto_id);
					$this->hook('insert');
				} else {
					$this->hook('insert-failed');
				}
				return $auto_id;
			}
			$result = $this->database()->insert($this->table(), $members, array(
				"id" => false
			));
		} catch (Database_Exception_Duplicate $e) {
			$this->hook('insert-failed', $e);
			throw new Exception_Object_Duplicate(get_class($this), $e->getMessage());
		}
		if (!$result) {
			$this->hook('insert-failed');
		} else {
			$this->hook('insert');
		}
		return $result;
	}
	private function update() {
		if ($this->option_bool("disable_database") || $this->option_bool("disable_database_update")) {
			return false;
		}
		$members = $this->_filter_store_members($this->members);
		$this->select_database();
		$members = $this->to_database($members);
		$where = array();
		foreach ($this->class->primary_keys as $primary_key) {
			if (!array_key_exists($primary_key, $members)) {
				throw new Exception_Object_Store(get_class($this), "Can not update when {primary_key} not set (All primary keys: {primary_key_samples}) (Member keys: {members_keys})", array(
					"primary_key" => $primary_key,
					"primary_key_samples" => json::encode($this->members($this->class->primary_keys)),
					"members_keys" => array_keys($members)
				));
			} else {
				$where[$primary_key] = $members[$primary_key];
				unset($members[$primary_key]);
			}
		}
		if (count($where) === 0) {
			throw new Exception_Semantics(__("Updating {class} without a where clause {primary_keys}", array(
				"class" => get_class($this),
				"primary_keys" => implode(", ", $this->class->primary_keys)
			)));
		}
		foreach ($members as $member => $value) {
			if (begins($member, "*")) {
				continue;
			}
			if (!array_key_exists($member, $this->original)) {
				continue;
			}
			if ($value === $this->original[$member]) {
				unset($members[$member]);
			}
		}
		$members = $this->hook('update_alter', $members);
		if (count($members) === 0) {
			if (self::$debug) {
				log::debug("Update of {class}:{id} - no changes", array(
					"class" => get_class($this),
					"id" => $this->id()
				));
			}
			return true;
		}
		$result = (count($members) > 0) ? $this->database()->update($this->table(), $members, $where) : true;
		if ($result) {
			$this->hook('update', $members);
			$this->original = $this->members + $this->original;
		} else {
			$this->hook('update-failed');
		}
		return $result;
	}
	function find($where = false) {
		$data = $this->exists($where);
		if (is_array($data)) {
			return $this->initialize($data, true);
		}
		return null;
	}
	function fetch_if_exists($where = null) {
		$row = $this->exists($where);
		if (is_array($row)) {
			return $this->object_status(self::status_exists)->initialize($row, true);
		}
		$this->object_status(self::status_unknown);
		return null;
	}
	function exists($where = false) {
		if (is_string($where) && !empty($where)) {
			if ($this->has_member($where)) {
				$where = array(
					$where => $this->member($where)
				);
			}
		}
		if (!is_array($where)) {
			$find_keys = $this->class->find_keys;
			if (empty($find_keys)) {
				return null;
			}
			$where = $this->class->duplicate_where;
			foreach ($find_keys as $k) {
				$where[$k] = $this->member($k);
			}
			$where = $this->to_database($where);
		}
		$this->select_database();
		$row = $this->database()->select_one_where($this->table(), $where, $this->class->find_order_by);
		if (!$row) {
			return null;
		}
		return $row;
	}
	function is_duplicate() {
		$duplicate_keys = $this->class->duplicate_keys;
		if (!$duplicate_keys) {
			return false;
		}
		
		$members = $this->members($duplicate_keys);
		$query = $this->query("X")->where($members)->what("*n", "COUNT(*)");
		if (!$this->is_new()) {
			$not_ids = $this->members($this->primary_keys());
			$not_ids = arr::ksuffix($not_ids, "|!=");
			$query->where($not_ids);
		}
		$result = to_bool($query->one_integer("n"));
		return $result;
	}
	function fetch_by_key($value = false, $column = false) {
		if (empty($column)) {
			$column = $this->find_key();
			if (empty($column)) {
				$column = $this->class->id_column;
			}
		}
		$row = $this->exists(array(
			$column => $value
		));
		if ($row === null) {
			return null;
		}
		return $this->initialize($row, true);
	}
	protected function fetch_query() {
		$primary_keys = $this->class->primary_keys;
		if (count($primary_keys) === 0) {
			throw new Exception_Semantics("{get_class} {method} can not access fetch_query when there's no primary keys defined", array(
				"get_class" => get_class($this),
				"method" => __METHOD__
			));
		}
		$keys = $this->members($primary_keys);
		$sql = $this->sql()->select(array(
			'what' => '*',
			'tables' => $this->table(),
			'where' => $keys,
			'limit' => 1
		));
		return $sql;
	}
	private function to_database($data, $insert = false) {
		return $this->class->to_database($this, $data, $insert);
	}
	function deleted($set = null) {
		if ($set === null) {
			return $this->_deleted($this->members);
		}
		$col = $this->class->column_deleted;
		if ($col) {
			$this->__set($col, $set);
		}
		return $this;
	}
	
	/**
	 * Is this deleted?
	 *
	 * @param unknown $data        	
	 */
	private function _deleted(array $data) {
		$col = $this->class->column_deleted;
		if (!$col) {
			return false;
		}
		if (!array_key_exists($col, $data)) {
			return false;
		}
		return to_bool($data[$this->column_deleted]);
	}
	
	/**
	 * Is this object polymorphic (multiple classes handling a single table)
	 *
	 * @param string $set
	 *        	Set polymorphic class - used internally from Class_Object
	 * @return $this boolean
	 */
	public function polymorphic($set = null) {
		if ($set === null) {
			return $this->class->polymorphic !== null ? true : false;
		}
		$this->polymorphic_leaf = $set;
		return $this;
	}
	
	/**
	 * Convert to true form.
	 * Override in subclasses to get custom polymorphic behavior.
	 *
	 * @return Object
	 */
	protected function _polymorphic() {
		$class = get_class($this);
		if (!$this->polymorphic_leaf) {
			return $this;
		}
		if (is_a($this, $this->polymorphic_leaf)) {
			return $this;
		}
		try {
			$result = $this->object_factory($this->polymorphic_leaf, $this->members, array(
				'initialize' => 'internal',
				'class_object' => $this->class->polymorphic_inherit_class ? $this->class : null
			) + $this->options);
			return $result;
		} catch (Exception_Class_NotFound $e) {
			global $zesk;
			log::error("Polymorphic conversion failed to class {polymorphic_leaf} from class {class}", array(
				"polymorphic_leaf" => $this->polymorphic_leaf,
				"class" => get_class($this)
			));
			$zesk->hooks->call("exception", $e);
			return $this;
		}
	}
	private function can_fetch() {
		foreach ($this->class->primary_keys as $pk) {
			$v = avalue($this->members, $pk);
			if (empty($v)) {
				return false;
			}
		}
		return true;
	}
	function fetch($mixed = null) {
		$mixed = $this->hook("fetch-enter", $mixed);
		if ($mixed !== null) {
			$this->initialize($mixed);
		}
		$hook_args = func_get_args();
		$this->need_load = false;
		if (!$this->can_fetch()) {
			throw new Exception_Object_Empty(get_class($this), "Missing primary key {primary_keys} values: {values}", array(
				"primary_keys" => $this->class->primary_keys,
				"values" => $this->members($this->class->primary_keys)
			));
		}
		$this->select_database();
		$obj = $this->fetch_object();
		if (!$obj) {
			if (($result = $this->hook_array('fetch_not_found', $hook_args, null)) !== null) {
				return $result;
			}
			throw new Exception_Object_NotFound(get_class($this));
		}
		if ($this->_deleted($obj)) {
			if (($result = $this->hook_array('fetch_deleted', $hook_args, null)) !== null) {
				return $result;
			}
			throw new Exception_Object_NotFound(get_class($this));
		}
		$result = $this->initialize($obj, true)->_polymorphic();
		return $result->hook_array("fetch", $hook_args, $result);
	}
	protected function fetch_object() {
		$sql = $this->fetch_query();
		return $this->database()->query_one($sql);
	}
	/**
	 * Retrieve errors during storing object
	 *
	 * @return array
	 */
	function store_errors() {
		return $this->option_array("store_error", array());
	}
	
	/**
	 * Retrieve the error string for the error when a duplicate is found in the database when
	 * storing
	 *
	 * @return string
	 */
	private function error_duplicate() {
		return $this->option("duplicate_error", "{indefinite_article} {name} with that name already exists. ({id})");
	}
	protected function error_store($member, $message) {
		$errors = $this->option_array("store_error", array());
		$errors[$member] = $message;
		$this->set_option("store_error", $errors);
		return null;
	}
	protected function store_queue() {
		foreach ($this->store_queue as $q) {
			$func = array_shift($q);
			call_user_func_array(array(
				$this,
				$func
			), $q);
		}
		$this->store_queue = array();
	}
	
	/**
	 *
	 * @see Model::store()
	 */
	function store() {
		/*
		 * Avoid infinite loops with objects linked back to themselves.
		 */
		if ($this->storing) {
			return $this;
		}
		
		try {
			$this->storing = true;
			/*
			 * Avoid storing identical items if possible
			 */
			/**
			 * When duplicating, we want to check is_duplicate only, so remove exists - not sure
			 */
			if ($this->is_duplicate()) {
				throw new Exception_Object_Duplicate(get_class($this), $this->error_duplicate(), array(
					"duplicate_keys" => $this->class->duplicate_keys,
					"name" => $this->class_name(),
					"id" => $this->id(),
					"indefinite_article" => zesk\Locale::indefinite_article($this->class->name)
				));
			}
			$this->store_object_members();
			$this->hook('store');
			/*
			 * Insert/Update
			 */
			if ($this->is_new()) {
				$id = $this->insert();
			} else {
				$this->update();
				$id = $this->id();
			}
			$this->store_queue();
			$this->is_new_cached = null;
			$this->storing = false;
			$this->original = $this->to_database($this->members);
			$this->hook("stored");
			return $this;
		} catch (Exception $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
			$this->storing = false;
			throw $e;
		}
	}
	
	/**
	 * Store any objects which are members, first
	 */
	private function store_object_members() {
		/*
		 * Store subobjects
		 */
		foreach ($this->class->has_one as $member => $class) {
			if ($class[0] === '*') {
				$class = $this->member(substr($class, 1));
				if (!$class) {
					continue;
				}
			}
			$result = $this->member($member);
			if ($result instanceof $class) {
				if (!$result->storing && ($result->is_new() || $result->changed())) {
					$result->store();
				}
			}
		}
	}
	protected function pre_register() {
		foreach ($this->class->has_one as $member => $class) {
			if ($class[0] === '*') {
				$class = $this->member(substr($class, 1));
			}
			$object = $this->member($member);
			/* @var $object Object */
			if ($object instanceof $class) {
				$object->register();
			}
		}
	}
	
	/**
	 * Register an object based on its "find_keys"
	 * Register means "create it if it doesn't exist, find it if it does"
	 *
	 * @return integer The ID of the registered object. Also the status is set to what happened, see
	 *         self::status_foo definitions either "insert", or "exists".
	 * @see Object::status_exists
	 * @see Object::status_insert
	 * @see Object::status_unknown
	 */
	function register($where = null) {
		// If we have all of our primary keys and has an auto_column, then don't bother registering.
		// Handles case when pre_register registers any objects within it. If everything is loaded OK, then we know
		// these are valid objects.
		if ($this->has_primary_keys() && $this->class->auto_column && !$this->option_bool(self::option_ignore_auto_column)) {
			return $this;
		}
		$this->pre_register();
		$data = $this->exists($where);
		if ($data === null) {
			try {
				$result = $this->store();
				if (!$result) {
					$this->object_status(self::status_unknown);
					return null;
				}
				return $result->object_status(self::status_insert);
			} catch (Database_Exception_Duplicate $e) {
				$data = $this->exists($where);
				if ($data === null) {
					throw $e;
				}
			}
			$this->initialize($data, true);
			$this->store();
		} else {
			$this->initialize($data, true);
		}
		return $this->object_status(self::status_exists);
	}
	
	/**
	 * Set/get result of object operation
	 *
	 * @param string $set        	
	 * @return string|$this
	 */
	function object_status($set = null) {
		if ($set !== null) {
			$this->status = $set;
			return $this;
		}
		return $this->status;
	}
	
	/**
	 *
	 * @return boolean
	 */
	function status_exists() {
		return $this->status === self::status_exists;
	}
	/**
	 *
	 * @return boolean
	 */
	function status_created() {
		return $this->status === self::status_insert;
	}
	private function _column_deleted_value() {
		return array(
			$this->class->column_deleted => true
		);
		// TODO: Support dates
	}
	public static function clean_database_object_members($class, $mixed) {
		/* @var $class_object Class_Object */
		$class_object = Object::cache_class($class, "class");
		$members = to_list($mixed);
		$this_id_column = $class_object->id_column;
		$__ = array(
			"class" => $class,
			"members" => $members
		);
		if (!$this_id_column) {
			log::error("{class}:clean_database_object_members({members}) {class} does not have an ID column", $__);
			return;
		}
		$ids = array();
		foreach ($members as $member) {
			$member_class = avalue($class_object->has_one, $member);
			if ($member_class[0] === '*') {
				continue;
			}
			if (!$member_class) {
				log::error("{class}:clean_database_object_members({member}) Member {member} is not a has_one", $__ + array(
					'member' => $member
				));
				continue;
			}
			$member_id_column = Class_Object::cache($member_class, 'id_column');
			if (!$member_id_column) {
				log::error("{class}:clean_database_object_members({member}) Member {member} does not have an ID column", $__ + array(
					'member' => $member
				));
				continue;
			}
			$ids = $ids + Object::class_query($class)->link($member, array(
				"required" => false,
				"alias" => "ref"
			))->where(array(
				"ref.$member_id_column" => null
			))->to_array($this_id_column, $this_id_column);
		}
		if (count($ids) > 0) {
			Object::class_delete(__CLASS__)->where($this_id_column, array_values($ids));
		}
	}
	protected function delete_unlinked_column($column, $class) {
		$unlinked = $this->query()
			->link($class, array(
			"alias" => "Link",
			"require" => false
		))
			->where("Link.ID", null)
			->what($column, $column)
			->to_array(null, $column);
		return $this->query_delete()->where($column, $unlinked)->execute();
	}
	
	/**
	 * For each of the "has_one" - if the target object does not exist, the delete this row
	 *
	 * Use with caution!
	 */
	protected function delete_unlinked() {
		$result = array();
		foreach ($this->class->has_one as $column => $class) {
			if ($class[0] === '*') {
				continue;
			}
			$result[$column] = $this->delete_unlinked_column($column, $class);
		}
		return $result;
	}
	
	/**
	 * Convert to string
	 */
	function __toString() {
		$id = $this->id();
		if (is_numeric($id)) {
			return strval($id);
		}
		if (is_array($id)) {
			ksort($id);
			$id = arr::flatten($id);
		}
		return php::dump($id);
	}
	
	/**
	 * Delete an object from the database
	 */
	public function delete() {
		if ($this->is_new()) {
			return false;
		}
		$cache = $this->object_cache();
		$cache->delete();
		
		if (zesk::getb("Object::disable_database")) {
			return false;
		}
		$where = array();
		foreach ($this->class->primary_keys as $k) {
			$where[$k] = $this->member($k);
		}
		$this->select_database();
		$this->database()->delete($this->table, $where);
		if (!$this->database()->affected_rows()) {
			$this->hook('delete-already');
			return false;
		}
		$this->hook('delete');
		return true;
	}
	
	/**
	 * Convert a variable to an ID
	 *
	 * @param $mixed mixed        	
	 * @return integer or null if can't be converted to integer
	 */
	public static function mixed_to_id($mixed) {
		if ($mixed instanceof Object) {
			return $mixed->id();
		}
		return to_integer($mixed, null);
	}
	
	/**
	 * Given a class $class, determine the default path to another class
	 *
	 * @param $class string        	
	 * @return string
	 */
	public function link_default_path_to($class) {
		return $this->class->link_default_path_to($class);
	}
	
	/**
	 * Walk path to $class while updating the query
	 *
	 * @param $class mixed        	
	 * @param $mixed array
	 *        	An array of link settings, or a string indicating the path to link to
	 *        	The settings in the array are:
	 *        	<code>
	 *        	"path" => "Object_Member.NextObject_Member.Column"
	 *        	</code>
	 * @return Database_Query_Select
	 */
	public function link_walk(Database_Query_Select $query, $mixed = null) {
		return $this->class->link_walk($this, $query, $mixed);
	}
	
	/**
	 * Convert an object into a notation transportable via JSON
	 *
	 * @param array $options        	
	 * @return array
	 */
	public function json(array $options = array()) {
		$depth = avalue($options, 'depth', 1);
		$members_only = avalue($options, 'members_only', false);
		/* Convert to JSONable structure */
		$object = array(
			"_class" => get_class($this),
			"_parent_class" => get_parent_class($this),
			"_primary_keys" => $this->members($this->primary_keys())
		);
		if ($depth === 0) {
			return $members_only ? $object['primary_keys'] : $object;
		}
		$members = array();
		$options['depth'] = $depth - 1;
		foreach ($this->members() as $member => $value) {
			if (is_scalar($value)) {
				$members[$member] = $value;
			} else if (is_object($value) && method_exists($value, "json")) {
				$members[$member] = $value->json($options);
			}
		}
		if ($members_only) {
			return $members;
		}
		return $object + $members;
	}
	
	/**
	 * Load object
	 *
	 * @param Widget $source        	
	 * @return $this
	 */
	protected function hook_control_loaded(Widget $source) {
		/* @var $object Object */
		$id = $source->request()->get($source->option('id_name', $this->class->id_column, null));
		if ($this->is_new() && !empty($id)) {
			$object = $this->initialize($id)->fetch();
			if (!$source->user_can("edit", $object)) {
				throw new Exception_Permission("edit", $object);
			}
		}
		return $this;
	}
	
	/**
	 * Hook to return a message when a control cancels editing
	 *
	 * @param Control $control        	
	 * @return string
	 */
	protected function hook_control_message_cancel(Control $control) {
		$cancelMessage = $control->option("cancel_message", __("No changes were made to the {class_name-context-object-singular}."));
		$cancelNewMessage = $control->option("cancel_new_message", __("{class_name-context-subject-singular} was not created."));
		return $this->is_new() ? $cancelNewMessage : $cancelMessage;
	}
	
	/**
	 * Hook to return message
	 *
	 * @param Control $control        	
	 * @return Ambigous <Model, Model, mixed, Hookable, string, array, number>
	 */
	protected function hook_control_message_store(Control $control) {
		$is_new = $this->is_new();
		$default_message = !$is_new ? __('Control:={class_name-context-subject-singular} "{display_name}" was updated.') : __('Control_Object_Edit:={class_name-context-subject-singular} "{display_name}" was added.');
		$store_message = $control->option("store_message", $default_message);
		if ($is_new) {
			$store_message = $control->option("store_new_message", $store_message);
		}
		return $store_message;
	}
	
	/**
	 * Hook to return message related to store errors
	 *
	 * @param Control $control        	
	 * @return string
	 */
	protected function hook_control_message_store_error(Control $control) {
		$name = strtolower($this->display_name());
		$message = __("{class_name-context-subject-indefinite-article} with that name already exists");
		$message = $this->option("store_error", $message);
		return $message;
	}
	
	/**
	 * Utility function for retrieving permissions.
	 * 
	 * Add static function permissions() to your subclass and call this to get useful permissions
	 *
	 * @param string $class        	
	 * @return array
	 */
	static function default_permissions($class) {
		$object = Object::cached($class);
		$name = $object->class->name;
		$names = zesk\Locale::plural($name);
		$__ = array(
			"object" => $name,
			"objects" => $names
		);
		return array(
			'view' => array(
				'title' => __('View {object}', $__),
				'class' => $class,
				"before_hook" => array(
					"allowed_if_all" => array(
						"$class::view all"
					)
				)
			),
			'view all' => array(
				'title' => __('View all {objects}', $__)
			),
			'edit' => array(
				'title' => __('Edit {object}', $__),
				'class' => $class,
				"before_hook" => array(
					"allowed_if_all" => array(
						"$class::edit all"
					)
				)
			),
			'edit all' => array(
				'title' => __('Edit all {objects}', $__)
			),
			'new' => array(
				'title' => __('Create {objects}', $__)
			),
			'delete all' => array(
				'title' => __('Delete any {objects}', $__)
			),
			'delete' => array(
				'title' => __('Delete {object}', $__),
				"before_hook" => array(
					"allowed_if_all" => array(
						"$class::delete all"
					)
				),
				'class' => $class
			),
			'list' => array(
				'title' => __('List {objects}', $__)
			)
		);
	}
	
	/**
	 *
	 * @see debug::_dump
	 * @return string
	 */
	public function _debug_dump() {
		$rows['primary_keys'] = $this->id();
		$rows['class'] = get_class($this->class);
		$rows['database'] = $this->database()->code_name();
		$rows['members'] = $this->members;
		return get_class($this) . " {\n" . text::indent(text::format_pairs($rows)) . "\n}\n";
	}
	
	/**
	 * Was deprecated 2012 - why? Where will this go?
	 *
	 * Replaced by ->variables()
	 *
	 * @param string $string        	
	 * @return array string
	 */
	public function words($string = null) {
		$name = $this->class->name;
		$spec['class_name-raw'] = $name;
		$spec['class_name'] = $spec['class_name-singular'] = zesk\Locale::translate($name, $this->locale);
		$spec['class_name-context-object'] = $spec['class_name-context-object-singular'] = $locale_class_name = strtolower($spec['class_name']);
		$spec['class_name-context-object-plural'] = zesk\Locale::plural($locale_class_name, $this->locale);
		$spec['class_name-context-subject'] = $spec['class_name-context-subject-singular'] = ucfirst($locale_class_name);
		$spec['class_name-context-subject-plural'] = ucfirst($spec['class_name-context-object-plural']);
		$spec['class_name-context-title'] = str::capitalize($spec['class_name-context-object']);
		$spec["class_name-context-subject-indefinite-article"] = zesk\Locale::indefinite_article($name, true);
		$spec['class_name-plural'] = zesk\Locale::plural($name, $this->locale);
		
		$name = $this->display_name();
		$spec['display_name'] = $name;
		
		if ($string === null) {
			return $spec;
		}
		$result = $this->apply_map(map($string, $spec));
		return $result;
	}
	protected function hook_router_argument(Route $route, $arg) {
		return $this->id($arg)->fetch();
	}
	
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/* DEPRECATED BELOW 
	 /*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	
	/**
	 *
	 * @deprecated 2015-04-15
	 * @see Object::status_exists
	 * @var string
	 */
	const REGISTER_Exists = self::status_exists;
	/**
	 *
	 * @deprecated 2015-04-15
	 * @see self::status_insert
	 * @var string
	 */
	const REGISTER_Insert = self::status_insert;
	/**
	 *
	 * @deprecated 2015-04-15
	 * @see self::status_unknown
	 * @var string
	 */
	const REGISTER_Failed = self::status_unknown;
	
	/**
	 * Returns true if the member is empty
	 * For multiple members, returns true if ANY member is empty
	 * For multiple members, returns false if no members are passed in
	 *
	 * @param mixed $member        	
	 * @return boolean
	 * @deprecated 2013-01-01
	 */
	function memberIsEmpty($member) {
		zesk::deprecated();
		return $this->member_is_empty($member);
	}
	
	/**
	 *
	 * @deprecated 2012-04-19
	 * @param unknown_type $codename        	
	 * @param unknown_type $name        	
	 */
	protected function cleanCodeName($codename = "CodeName", $name = "Name") {
		zesk::deprecated();
		if ($this->member_is_empty($codename)) {
			$this->set_member($codename, self::clean_code_name($this->member($name)));
		} else {
			$this->set_member($codename, self::clean_code_name($this->member($codename)));
		}
		return false;
	}
	
	/**
	 * Does this object define the member given?
	 *
	 * @see Object::member_empty
	 * @param string $member        	
	 * @return boolean
	 * @deprecated 2014-01-01
	 */
	function hasMember($member) {
		zesk::deprecated();
		return array_key_exists($member, $this->class->column_types) || array_key_exists($member, $this->class->has_many);
	}
	
	/**
	 *
	 * @deprecated 2013-10-23
	 */
	function fields() {
		zesk::deprecated();
		return $this->columns();
	}
	
	/**
	 *
	 * @deprecated 2013-10-23
	 */
	function fieldList() {
		zesk::deprecated();
		return $this->columns();
	}
	
	/**
	 *
	 * @deprecated 2013-10-23
	 * @param unknown $string        	
	 */
	public function objectMap($string = null) {
		zesk::deprecated();
		return $this->words($string);
	}
	
	/**
	 *
	 * @see Object::database_name()
	 * @deprecated 2013-10-23
	 */
	function dbname() {
		zesk::deprecated();
		return $this->database_name();
	}
	
	/**
	 *
	 * @deprecated 2013-10-23
	 * @return Database
	 */
	public function db() {
		zesk::deprecated();
		return $this->database();
	}
	
	/**
	 *
	 * @deprecated 2013-10-23
	 * @return string
	 */
	function className() {
		zesk::deprecated();
		return $this->class_name();
	}
	
	/**
	 *
	 * @deprecated 2014-02-11 use set_member
	 * @param string $member        	
	 * @param mixed $v
	 *        	Value
	 * @param boolean $overwrite
	 *        	Write value if it exists
	 */
	function memberSet($member, $v = null, $overwrite = true) {
		zesk::deprecated();
		return $this->set_member($member, $v, $overwrite);
	}
	
	/**
	 * Retrieve a member as an integer
	 *
	 * @param $member string
	 *        	Name of member
	 * @param $def mixed
	 *        	Default value to return if can't convert to integer
	 * @return integer
	 * @deprecated 2013-10-17
	 */
	function memberInteger($member, $def = null) {
		zesk::deprecated();
		return $this->member_integer($member, $def);
	}
	
	/**
	 * Retrieve a member as a boolean value
	 *
	 * @param $member string
	 *        	Name of member
	 * @param $def mixed
	 *        	Default value to return if can't convert to boolean
	 * @return integer
	 * @deprecated 2013-10-17
	 */
	function memberBoolean($member, $def = null) {
		return $this->member_boolean($member, $def);
	}
	
	/**
	 * Set/get result of register call
	 *
	 * @param string $set        	
	 * @return string this
	 * @deprecated 2015-04-15
	 * @see Object::object_status
	 */
	function register_result($set = null) {
		if ($set !== null) {
			$this->status = $set;
			return $this;
		}
		return $this->status;
	}
	
	/**
	 * Retrieve a cache attached to this object only
	 *
	 * @deprecated 2014-0905
	 * @see Object::object_cache
	 * @param $cache_id string
	 *        	A specific cache for this object, or NULL for the global cahce fo this object
	 * @return Cache
	 */
	function objectCache($cache_id = null) {
		zesk::deprecated();
		return $this->object_cache($cache_id);
	}
	
	/**
	 * Name/value pairs used to generate the schema for this object
	 *
	 * @return array
	 */
	public function schema_map() {
		return $this->option_array("schema_map") + array(
			'table' => $this->table()
		);
	}
}
