<?php

/**
 * Class abstraction for ORM Relational Map
 *
 * This is where the magic happens for ORMs
 *
 * Copyright &copy; 2015 Market Acumen, Inc.
 * @author kent
 * @see ORM
 */
namespace zesk;

/**
 *
 * @see ORM
 */
class Class_ORM extends Hookable {

	/**
	 * For ID columns
	 *
	 * @var string
	 */
	const type_id = "id";

	/**
	 * Plain old text data in the database
	 *
	 * @var string
	 */
	const type_text = "text";
	/**
	 * Plain old text data in the database (varchar)
	 *
	 * @var string
	 */
	const type_string = "string";

	/**
	 * This column serves as text data for polymorphic objects
	 *
	 * On store, saves current object class polymorphic name
	 * On loading, creates into new object
	 *
	 * @var string
	 */
	const type_polymorph = "polymorph";

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	const type_object = "orm";

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	const type_orm = "orm";

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	const type_created = "created";

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	const type_modified = "modified";

	/**
	 * String information called using serialize/unserialize.
	 * Column should be a blob (not text)
	 *
	 * @var string
	 */
	const type_serialize = "serialize";

	/**
	 * Convert data to/from a JSON string in the database.
	 * Column should be a blob (not text)
	 *
	 * @var string
	 */
	const type_json = "json";

	/**
	 * Convert data to/from an integer
	 *
	 * @var string
	 */
	const type_integer = "integer";

	/**
	 * Database string (char)
	 *
	 * @var string
	 */
	const type_character = "character";

	/**
	 * Single-precision floating point number
	 *
	 * @var string
	 */
	const type_real = "real";

	/**
	 *
	 * @var string
	 */
	const type_float = "float";
	/**
	 *
	 * @var string
	 */
	const type_double = "double";
	/**
	 *
	 * @var string
	 */
	const type_decimal = "decimal";
	/**
	 *
	 * @var string
	 */
	const type_byte = "byte";
	/**
	 *
	 * @var string
	 */
	const type_binary = "binary";
	/**
	 *
	 * @var string
	 */
	const type_boolean = "boolean";
	/**
	 *
	 * @var string
	 */
	const type_timestamp = "timestamp";
	/**
	 *
	 * @var string
	 */
	const type_datetime = "datetime";
	/**
	 *
	 * @var string
	 */
	const type_date = "date";
	/**
	 *
	 * @var string
	 */
	const type_time = "time";
	/**
	 *
	 * @var string
	 */
	const type_ip = "ip";
	/**
	 *
	 * @var string
	 */
	const type_ip4 = "ip4";
	/**
	 *
	 * @var string
	 */
	const type_crc32 = "crc32";
	/**
	 *
	 * @var string
	 */
	const type_hex32 = "hex";
	/**
	 *
	 * @var string
	 */
	const type_hex = "hex";

	/**
	 * Application class associated with this Class_ORM
	 *
	 * @var string
	 */
	public $application_class = null;

	/**
	 * PHP Class which created this (type ORM)
	 *
	 * @var string
	 */
	public $class = null;

	/**
	 * String name of the database to use
	 *
	 * @var string
	 */
	public $database_name = null;

	/**
	 * Name of the ORM which should share this database with.
	 * String must contain namespace prefix, if any.
	 *
	 * Allows objects to be grouped into a database (by module) or functionality, for example.
	 *
	 * @var string
	 */
	protected $database_group = null;

	/**
	 * Database name where this object resides.
	 * If not specified, the default database.
	 * <code>
	 * protected $database = "tracker";
	 * </code>
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
	public $table = null;

	/**
	 * English name of this object for possible display (sorry...)
	 *
	 * If not specified, class name.
	 *
	 * e.g.
	 * <code>
	 * protected $name = "Article Comment";
	 * </code>
	 *
	 * @var string
	 */
	public $name = null;

	/**
	 * Data structure implemented March 2014
	 * Merges column_defaults, column_types, and Schema_classname into a single
	 * structure.
	 * If this is empty, it is generated based on other settings.
	 * If it is not empty, it overwrites all other entries
	 *
	 * Structure of each column is:
	 *
	 * 'type' => self::type_foo
	 * 'default' => default value, if any
	 * 'class' => object class of target, if any (replaces $has_one)
	 *
	 * @var array
	 */
	public $members = array();

	/**
	 * Specify the base polymorphic class here, or true if it uses a method
	 *
	 * @var mixed
	 */
	public $polymorphic = null;

	/**
	 * Will an object's Class representation change depending on polymorphic state?
	 *
	 * e.g.
	 * set to true for Bear, Bear_Fuzzy, Bear_Bare all sharing Class_Bear.
	 * set to false for Bear, Bear_Fuzzy, Bear_Bare all using respective Class_Bear,
	 * Class_Bear_Fuzzy, Class_Bear_Bare depending on polymorphic state.
	 *
	 * The latter (false) allows you to have objects whose data representation (linked objects)
	 * changes depending on polymorphic class setup.
	 *
	 * Class_Bear_Fuzzy could link to different has_many values as Class_Bear_Bare.
	 *
	 * @var boolean
	 */
	public $polymorphic_inherit_class = true;

	/**
	 * Unique internal, programmer name for this object.
	 * If not specified, class name.
	 * e.g.
	 * <code>
	 * protected $code_name = "ArticleComment";
	 * </code>
	 *
	 * @var string
	 */
	public $code_name = null;

	/**
	 * <code>
	 * protected $schema_file = 'MyORM.sql';
	 * </code>
	 * File which contains the schema for this object, found in:
	 * ORM_Schema_File::template_schema_paths()
	 * If not specified, it's get_class($this) . ".sql"
	 *
	 * @var string
	 */
	public $schema_file = null;

	/**
	 * List of columns in this object.
	 * If not specified, automatically determined from database table.
	 * <code>
	 * protected $columns = array("ID","Article","User","Session","Comment");
	 * </code>
	 *
	 * This value is always overwritten as of April 2014. The value is implied
	 * from $column_types and $has_one, unless $load_database_columns is set to true.
	 *
	 * @deprecated 2014-04-01 use $this->column_types
	 * @see $this->column_types
	 * @var array
	 */
	public $columns = array();

	/**
	 * <code>
	 * protected $column_types = array("column" => "type",...)
	 * </code>
	 * Can specify special database types:
	 * - "hex" does hex/unhex
	 * - "integer" converts to integer
	 * - "boolean" converts to boolean from integer
	 * - "serialize" serializes PHP objects
	 * - "crc" is a CRC checksum on another column specified by ->checksum_column
	 *
	 * @var array
	 */
	public $column_types = array();

	/**
	 * Whether to dynamically load the object columns from the database
	 *
	 * @var boolean
	 */
	public $load_database_columns = false;

	/**
	 * Member defaults: fill in only defaults you want to set
	 */
	public $column_defaults = null;

	/**
	 * Which column to use in a CRC checksum
	 *
	 * @var string
	 */
	public $crc_column = null;

	/**
	 * The default column for displaying this object's name
	 *
	 * @var string
	 */
	public $name_column = null;

	/**
	 * Name of the column which uniquely identifies this object in the table.
	 * Default is "id"
	 *
	 * @var string
	 */
	public $id_column = null;

	/**
	 * Name of the columns which uniquely identifies this object in the table.
	 *
	 * @var array
	 */
	public $primary_keys = null;

	/**
	 * Name of the column which is automatically incremented upon saves
	 * Set to the blank string if no column exists.
	 *
	 * @var string
	 */
	public $auto_column = null;

	/**
	 * List of columns used by default to look up an object for a match
	 *
	 * @var array
	 */
	public $find_keys = null;

	/**
	 * When finding, order results this way and retrieve the first item
	 *
	 * @var array
	 */
	public $find_order_by = null;

	/**
	 * Add this to the where clause when searching for duplicates
	 *
	 * @var array
	 */
	public $duplicate_where = array();

	/**
	 * List of columns which are used to determine if a duplicate exists in the database.
	 *
	 * @var array
	 */
	public $duplicate_keys = null;

	/**
	 * Use UTC timestamps for Created and Modified columns.
	 * Default value is set to boolean option "utc_timestamps", then
	 * global "ORM::utc_timestamps", then true.
	 *
	 * @var boolean
	 */
	public $utc_timestamps = null;

	/**
	 * Whether this object has its columns determined programmatically.
	 * Set by ORM class, read-only by subclasses
	 *
	 * @var boolean
	 */
	public $dynamic_columns = false;

	/**
	 * Function to call to get a field as implemented in ORM subclass (not Class_ORM subclass!)
	 * Method should be identical to __get prototype.
	 * (Allows reuse.)
	 *
	 * @var array
	 */
	public $getters = array();

	/**
	 * Function to call to set a field as implemented in ORM subclass (not Class_ORM subclass!)
	 * Method should be identical to __set prototype.
	 * (Allows reuse.)
	 *
	 * @var array
	 */
	public $setters = array();

	/**
	 * Specify one-to-many or many-to-many relationships
	 * <code>
	 * protected $has_many = array( <em>has many spec</em>,...)
	 * </code>
	 * Where <em>has many spec</em> is:
	 * <code>
	 * array(
	 * "class" => "class of linked object", (required)
	 * "table" => join table for multiple objects (optional)
	 * "foreign_key" => name of the column in the foreign table which refers to this object
	 * (optional)
	 * "far_key" => name of the column in the foreign table which refers to the remote object
	 * (optional)
	 * )
	 * </code>
	 * e.g.
	 * <code>
	 * class Article {
	 * ...
	 * protected $has_many = array("class" => "ArticleComment", "foreign_key" => "Article");
	 * ...
	 * </code>
	 * Note that the default foreign key is the class name of the current class, so naming columns
	 * after class names will work well. As well, the default "far" key is the class name specified.
	 * So this table:
	 * <code>
	 * CREATE TABLE Article_Referrer (
	 * Article integer unsigned NOT NULL,
	 * Blog_Host integer unsigned NOT NULL
	 * INDEX aac (Article,ArticleComment)
	 * );
	 * </code>
	 * Would work well as an intermediate table between objects Article and Blog_Host
	 *
	 * @var array
	 */
	public $has_many = array();

	/**
	 * <code>
	 * protected $has_one = array("column" => "class name",...)
	 * </code>
	 * Will automatically convert members to objects with ID of column.
	 *
	 * You can dynamically set the class using another member by specifying a "*" before the other
	 * member name instead of a class
	 * name.
	 *
	 * So:
	 *
	 * <code>
	 * public $has_one = array("field" => "*other_field");
	 * </code>
	 *
	 * Will look at string member "other_field" to determine the class to use for "field".
	 *
	 * Another example:
	 *
	 * <code>
	 * public $has_one = array(
	 * "Comment" => "Article_Comment",
	 * "Related" => "*Related_Class"
	 * );
	 * </code>
	 *
	 * Article->Comment will return an object of class Article_Comment
	 * Article->Related will return an object of class (Article->Related_Class)
	 *
	 * @var array
	 */
	public $has_one = array();

	/**
	 * $this->has_one flipped with identical columns as arrays
	 *
	 * @var array
	 */
	public $has_one_flip = array();

	/**
	 * List of options in this object which should be passed to sub-objects
	 * Can be an array or a semicolon separated list.
	 *
	 * @var array
	 */
	public $inherit_options = null;

	/**
	 * The deleted column to support soft deletions
	 *
	 * @var string
	 */
	public $column_deleted = null;

	/**
	 * List of columns, which, when they change, will invalidate the cache for this object.
	 *
	 * @var array|string
	 */
	public $cache_column_names = null;

	/**
	 * When converting to JSON, use these options by default.
	 * Parameter options take precedence over these.
	 *
	 * @var array
	 */
	public $json_options = array();
	/*
	 *  Lookup list of class => member
	 */
	private $has_many_objects = array();

	/*
	 *  Cached table columns
	 */
	private $table_columns = array();

	/**
	 * Class cache
	 *
	 * @var array:ORM_Class
	 */
	static $classes = array();

	/**
	 * Class cache
	 *
	 * @var boolean
	 */
	static $classes_dirty = false;

	/**
	 * List of deferrable class linkages
	 *
	 * @var
	 *
	 */
	static $defer_class_links = array();

	/**
	 * Cache database columns here
	 *
	 * @var array:array
	 */
	static $column_cache = array();

	/**
	 * If you modify the database structure dynamically, you should call this to force column
	 * recomputation
	 */
	public static function dirty() {
		self::$classes = array();
		self::$defer_class_links = array();
		self::_column_cache()->erase()->delete();
	}

	/**
	 * Handle namespace objects intelligently and preserve namespace (\ group), prefixing class name
	 * (_ group)
	 *
	 * @inline_test assertEqual(Class_ORM::object_to_class('zesk\Dude'), 'zesk\Class_Dude');
	 * @inline_test assertEqual(Class_ORM::object_to_class('a\b\c\d\e\f\g\Dude'),
	 * 'a\b\c\d\e\f\g\Class_Dude');
	 *
	 * @param unknown $classname
	 */
	public static function object_to_class($classname) {
		list($namespace, $suffix) = pairr($classname, "\\", null, $classname, "left");
		return $namespace . 'Class_' . $suffix;
	}

	/**
	 *
	 * @deprecated 2017-11?
	 * @todo remove this probably
	 */
	public static function classes_exit(Application $application) {
		if (self::$classes_dirty) {
			$application->hooks->call("Class_ORM::classes_save", self::$classes);
		}
	}
	/**
	 * Create a new class instance - should only be called from ORM
	 *
	 * @param ORM $object
	 * @return Zesk_Class
	 */
	public static function instance(ORM $object, array $options = array(), $class = null) {
		if ($class === null) {
			$class = get_class($object);
		}
		$application = $object->application;
		$lowclass = strtolower($class);
		if (!is_array(self::$classes)) {
			self::$classes = $application->hooks->call_arguments("Class_ORM::classes_load", array(), array());
			$application->hooks->add("exit", "Class_ORM::classes_exit", array(
				'arguments' => array(
					$application
				)
			));
		}
		if (array_key_exists($lowclass, self::$classes)) {
			return self::$classes[$lowclass];
		}
		$class_class = self::object_to_class($class);
		$instance = self::$classes[$lowclass] = $application->objects->factory($class_class, $object);
		self::$classes_dirty = true;
		return $instance;
	}

	/**
	 *
	 * @ignore
	 *
	 */
	public function __sleep() {
		return array_merge(array(
			'application_class',
			'class',
			'database_name',
			'table',
			'name',
			'members',
			'polymorphic',
			'polymorphic_inherit_class',
			'code_name',
			'schema_file',
			'columns',
			'column_types',
			'load_database_columns',
			'column_defaults',
			'crc_column',
			'name_column',
			'id_column',
			'primary_keys',
			'auto_column',
			'find_keys',
			'find_order_by',
			'duplicate_where',
			'duplicate_keys',
			'utc_timestamps',
			'dynamic_columns',
			'getters',
			'setters',
			'has_many',
			'has_one',
			'inherit_options',
			'column_deleted',
			'cache_column_names'
		), parent::__sleep());
	}

	/**
	 *
	 * @see wakeup
	 */
	public function __wakeup() {
		$this->application = zesk()->application();
		$this->application->hooks->register_class($this->class);
	}

	/**
	 * Lazy link classes together with has_many functionality
	 *
	 * @param string $class
	 * @param string $member
	 *        	Member name to use for iteration, etc.
	 * @param array $many_spec
	 *        	Many specification
	 * @throws Exception_Semantics
	 */
	public static function link_many($class, $member, array $many_spec) {
		if (!array_key_exists('class', $many_spec)) {
			throw new Exception_Semantics("many_spec for class {class} must contain key 'class' for member {member}", compact("class", "member"));
		}
		$lowclass = strtolower($class);
		if (array_key_exists($lowclass, self::$classes)) {
			$class = self::$classes[$lowclass];
			$class->_add_many($member, $many_spec);
		} else {
			if (isset(self::$defer_class_links[$lowclass][$member])) {
				throw new Exception_Semantics("Double link_many added for {class} {member}", compact("class", "member"));
			}
			self::$defer_class_links[$lowclass][$member] = $many_spec;
		}
	}

	/**
	 * When registering the object, add deferred
	 *
	 * @param unknown $class
	 */
	private function _add_defer_link_many($class) {
		if (count(self::$defer_class_links) === 0) {
			return;
		}
		foreach ($this->application->classes->hierarchy($class) as $parent_class) {
			$lowclass = strtolower($parent_class);
			if (array_key_exists($lowclass, self::$defer_class_links)) {
				foreach (self::$defer_class_links[$lowclass] as $member => $many_spec) {
					$this->_add_many($member, $many_spec);
				}
				// No delete for now: Do we want to allow multiple links across many subclasses? Probably.
				//unset(self::$defer_class_links[$lowclass]);
			}
		}
	}
	protected function _add_many($member, array $many_spec) {
		if (!array_key_exists('class', $many_spec)) {
			throw new Exception_Semantics("many_spec for class {class} must contain key 'class' for member {member}", compact("class", "member"));
		}
		$class = $this->application->objects->resolve($many_spec['class']);
		if (avalue($many_spec, 'default')) {
			ArrayTools::prepend($this->has_many_objects, $class, $member);
		} else {
			ArrayTools::append($this->has_many_objects, $class, $member);
		}
		$this->has_many[$member] = map($many_spec, array(
			'table' => $this->table
		));
		return $this;
	}

	/**
	 * Retrieve object or classes from cache
	 *
	 * @deprecated 2017-08 Use application functions for this
	 * @param string $class
	 * @param string $component
	 *        	Optional component to retrieve
	 * @throws Exception_Semantics
	 * @return Ambigous <mixed, array>
	 */
	public static function cache($class, $component = "") {
		zesk()->deprecated();
		return zesk()->application()->_class_cache($class, $component);
	}

	/**
	 * @deprecated 2017-08
	 * @param unknown $class
	 */
	public static function cache_dirty($class = null) {
		zesk()->deprecated();
		return zesk()->application()->clear_class_cache($class);
	}

	/**
	 * Constructor
	 *
	 * @throws Exception_Semantics
	 */
	public function __construct(ORM $object, array $options = array()) {
		$app = $object->application;
		parent::__construct($app, $options);
		$this->inherit_global_options();
		$this_class = $object->class_object();
		// Handle polymorphic classes - create correct Class and use correct base class
		$this_class = $this->class = is_string($this_class) ? $this_class : get_class($object);

		$this->configure($object);
		// In case configure changes it
		$this_class = $this->class;
		if (count($this->column_types) === 0) {
			$this->dynamic_columns = true;
		}
		if (empty($this->code_name)) {
			$this->code_name = str::rright($this_class, "\\");
		}
		if (empty($this->name)) {
			$this->name = $this_class;
		}
		if (empty($this->table)) {
			$this->table = $this->option("table", $object->option("table"));
			if (empty($this->table)) {
				$prefix = $this->option("table_prefix", $object->option("table_prefix"));
				$this->table = $prefix . $this->code_name;
			}
		}
		if (is_array($this->primary_keys)) {
			if (count($this->primary_keys) === 1) {
				$this->id_column = $this->primary_keys[0];
			}
		} else if ($this->id_column === null) {
			$this->id_column = $this->option('id_column_default', 'id');
			if ($this->id_column && $this->primary_keys === null) {
				$this->primary_keys = array(
					$this->id_column
				);
			}
		} else if ($this->id_column === false) {
			$this->primary_keys = array();
			$this->id_column = null;
		} else {
			$this->primary_keys = array(
				$this->id_column
			);
		}
		if ($this->auto_column === null) {
			$auto_type = avalue($this->column_types, strval($this->id_column));
			$this->auto_column = ($auto_type === null || $auto_type === self::type_id) ? $this->id_column : false;
		}
		if (empty($this->find_keys)) {
			$this->find_keys = $this->primary_keys;
		}
		if (empty($this->duplicate_keys)) {
			$this->duplicate_keys = array();
		}
		$this->_add_defer_link_many($this_class);
		if (!empty($this->has_many)) {
			foreach ($this->has_many as $member => $many_spec) {
				if (!is_array($many_spec)) {
					throw new Exception_Semantics('many_spec for class {class} must have array value for member {member}', array(
						"class" => $this_class,
						"member" => $member
					));
				}
				if (!array_key_exists('class', $many_spec)) {
					throw new Exception_Semantics('many_spec for class {class} must contain key \'class\' for member {member}', array(
						"class" => $this_class,
						"member" => $member
					));
				}
				$class = $many_spec['class'];
				if (avalue($many_spec, 'default')) {
					ArrayTools::prepend($this->has_many_objects, $class, $member);
				} else {
					ArrayTools::append($this->has_many_objects, $class, $member);
				}
			}
			$this->has_many = map($this->has_many, array(
				'table' => $this->table
			));
		}
		if (!empty($this->has_one)) {
			$this->has_one_flip = array();
			foreach ($this->has_one as $member => $class) {
				if ($class[0] !== '*') {
					$this->has_one[$member] = $class = $app->objects->resolve($class);
					ArrayTools::append($this->has_one_flip, $class, $member);
				}
			}
		}
		$this->initialize_database($object);
		if (empty($this->utc_timestamps)) {
			$this->utc_timestamps = $this->option_bool("utc_timestamps");
		}
		if (count($this->columns) > 0) {
			$app->logger->warning("{class} public \$columns is deprecated, use \$column_types", array(
				"class" => get_class($this)
			));
			$app->deprecated();
		}
		$this->init_columns(null);
		$this->_column_defaults();
		$this->initialize();
		if (count($this->column_types) === 0 && count($this->table_columns) > 0) {
			$this->imply_column_types();
		}

		$this->application->hooks->register_class($this->class);
	}
	protected function initialize_database(ORM $object) {
		if (!empty($this->database_group) && $this->database_group !== $this->class) {
			$this->database_name = $this->database = $this->application->object($this->database_group)->database_name();
		} else {
			if (empty($this->database)) {
				$this->database = $this->option("database", $object->option("database"));
			}
			if (empty($this->database_name) && is_string($this->database)) {
				$this->database_name = $this->database;
			}
		}
	}
	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 */
	protected function configure(ORM $object) {
	}
	/**
	 * Overwrite this in subclasses to change stuff upon instantiation
	 */
	protected function initialize() {
	}

	/**
	 * Load columns from database
	 *
	 * @param string $spec_columns
	 * @throws Exception
	 * @return boolean
	 */
	final public function init_columns() {
		if (!$this->load_database_columns && count($this->column_types) > 0) {
			if (!is_array($this->primary_keys)) {
				backtrace();
			}
			$this->columns = array_merge(array_keys($this->column_types + $this->has_one), $this->primary_keys);
			return true;
		}
		if (!$this->load_columns()) {
			$this->columns = array();
			return false;
		}
		if (!is_array($this->columns)) {
			throw new Exception(get_class($this) . " specification does not have array fields: " . strval($this->columns));
		}
		return true;
	}

	/**
	 * Column cache
	 *
	 * @return Cache
	 */
	private static function _column_cache() {
		$cache = Cache::register(array(
			"database-columns"
		));
		return $cache;
	}
	/**
	 * Load database columns from database/cache
	 *
	 * @param string $force
	 * @return boolean
	 */
	private function load_columns($force = false) {
		if (!empty($this->table_columns) && !$force) {
			return false;
		}
		$cache = $this->_column_cache();
		$cache_key = $this->table;

		$columns = $cache->get($cache_key);
		if (is_array($columns)) {
			$this->columns = $columns;
			return true;
		}
		$return = true;
		try {
			$columns = $this->database()->table_columns($this->table);
			$this->table_columns = array();
			foreach ($columns as $object) {
				$name = $object->name();
				$this->table_columns[$name] = $object->sql_type();
			}
			self::$column_cache[$cache_key] = $this->table_columns;
		} catch (Database_Exception_Table_NotFound $e) {
			$this->application->hooks->call("exception", $e);
			unset(self::$column_cache[$cache_key]);
			$this->table_columns = array();
			$return = false;
		} catch (Exception $e) {
			$this->application->hooks->call("exception", $e);
			unset(self::$column_cache[$cache_key]);
			$this->table_columns = array();
			$return = false;
		}
		$this->columns = array_keys($this->table_columns);
		if ($return) {
			$cache->set($cache_key, $this->columns);
		}
		return $return;
	}

	/**
	 * Given a class $class, determine the default path to the class
	 *
	 * @param $class string
	 * @return string
	 */
	final public function link_default_path_to($class) {
		$fields = avalue($this->has_one_flip, $class);
		if (is_array($fields)) {
			return $fields[0];
		}
		if (is_string($fields)) {
			return $fields;
		}
		$has_many = avalue($this->has_many_objects, $class);
		if ($has_many === null) {
			return null;
		}
		if (is_array($has_many)) {
			$has_many = $has_many[0];
		}
		return $has_many;
	}
	final public function link_walk(ORM $object, Database_Query_Select $query, $mixed = null) {
		$generator = $this->database()->sql();
		$path = avalue($mixed, 'path');
		if ($path === null) {
			throw new Exception_Semantics($this->class . "::link_walk: No path in " . serialize($mixed));
		}
		list($segment, $path) = pair($path, ".", $path, null);
		$join_type = avalue($mixed, "type", avalue($mixed, "require", true) ? "INNER" : "LEFT OUTER");
		if (array_key_exists($segment, $this->has_one)) {
			$to_class = $this->has_one[$segment];
			if ($to_class[0] === '*') {
				$to_class = $object->member(substr($to_class, 1));
			}
			$to_object = $this->application->object($to_class);

			if ($path === null) {
				$alias = aevalue($mixed, 'alias', $segment);
			} else {
				$alias = $segment;
			}
			$prev_alias = aevalue($mixed, 'previous_alias', $query->alias());
			if (!$query->find_alias($alias)) {
				$on = array(
					'*' . $generator->column_alias($to_object->id_column(), $alias) => $generator->column_alias($segment, $prev_alias)
				);
				$query->join_object($join_type, $to_class, $alias, $on);
			}
			if ($path === null) {
				return $query;
			}
			$mixed['path_walked'][] = $segment;
			$mixed['path'] = $path;
			$mixed['previous_alias'] = $alias;
			return $to_object->link_walk($query, $mixed);
		}
		$has_many = $this->has_many($object, $segment);
		if ($has_many) {
			$to_object = $has_many['object'];
			$to_class = $has_many['class'];
			if ($path === null) {
				$alias = aevalue($mixed, 'alias', $segment);
			} else {
				$alias = $segment;
			}
			$prev_alias = aevalue($mixed, 'previous_alias', $query->alias());
			$mid_link = $alias . "_Link";
			if ($this->_has_many_query($object, $query, $has_many, $mid_link, $prev_alias, $join_type)) {
				// joining on intermediate table
				$on = array(
					'*' . $generator->column_alias($has_many['far_key'], $mid_link) => $generator->column_alias($to_object->id_column(), $alias)
				);
			} else {
				// joining on intermediate table
				$on = array(
					'*' . $generator->column_alias($has_many['foreign_key'], $alias) => $generator->column_alias($object->id_column(), $prev_alias)
				);
			}
			if (array_key_exists("on", $mixed) && is_array($add_on = $mixed['on'])) {
				foreach ($add_on as $k => $v) {
					$on["$alias.$k"] = $v;
				}
			}
			$query->join_object($join_type, $to_class, $alias, $on);
			if ($path === null) {
				return $query;
			}
			$mixed['path_walked'][] = $segment;
			$mixed['path'] = $path;
			$mixed['previous_alias'] = $segment;

			return $to_object->link_walk($query, $mixed);
		}
		$has_alias = $query->find_alias($segment);
		if ($has_alias) {
			$to_object = $this->application->object($has_alias);

			$mixed['path_walked'][] = $segment;
			$mixed['path'] = $path;
			$mixed['previous_alias'] = $segment;

			return $to_object->link_walk($query, $mixed);
		}
		throw new Exception_Semantics("No path $segment found in " . $this->class . "::link_walk");
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @param $query Database_Query_Select
	 * @param $many_spec array
	 * @param $alias string
	 *        	Optional alias to use for the intermediate table
	 * @param $reverse boolean
	 *        	If linking from far object to this
	 * @return boolean true if intermediate table is used, false if not
	 */
	final public function _has_many_query(ORM $this_object, Database_Query_Select $query, array $many_spec, &$alias = "J", $link_alias = null, $join_type = true, $reverse = false) {
		$result = false;
		$table = avalue($many_spec, 'table');
		$foreign_key = $many_spec['foreign_key'];
		$query_class = $query->object_class();
		$gen = $this->database()->sql();
		if (is_bool($join_type)) {
			$join_type = $join_type ? "INNER" : "LEFT OUTER";
		}
		if ($table !== null) {
			$result = true;
			// $class = $many_spec['class'];
			$object = $many_spec['object'];
			$far_key = $many_spec['far_key'];
			$alias = $alias . "_join";
			if ($link_alias === null) {
				$link_alias = $query->alias();
			}
			if ($reverse) {
				$id_column = $object->id_column();
				$on = array(
					'*' . $gen->column_alias($far_key, $alias) => $gen->column_alias($id_column, $link_alias)
				);
			} else {
				$id_column = $this->id_column;
				$on = array(
					'*' . $gen->column_alias($foreign_key, $alias) => $gen->column_alias($id_column, $link_alias)
				);
			}

			$query->join_object($join_type, $object, $alias, $on, $table);
		}
		$logger = $this_object->application->logger;
		$this_alias = $alias;
		if (!$this_object->is_new()) {
			if (ORM::$debug) {
				$logger->debug(get_class($this_object) . " is NOT new");
			}
			$this_alias = $query_class === get_class($this) ? $query->alias() : $alias;
			$query->where("*" . $gen->column_alias($foreign_key, $this_alias), $this_object->id());
		} else {
			if (ORM::$debug) {
				$logger->notice(get_class($this_object) . " is  new");
			}
		}

		if (array_key_exists("order_by", $many_spec)) {
			$query->order_by(ArrayTools::prefix(to_list($many_spec['order_by']), "$this_alias."));
		}
		return $result;
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @param $query Database_Query_Select
	 * @param $many_spec array
	 * @param $alias string
	 *        	Optional alias to use for the intermediate table
	 * @param $reverse boolean
	 *        	If linking from far object to this
	 * @return boolean true if intermediate table is used, false if not
	 * @todo implement this
	 */
	final public function _has_many_query_update(ORM $this_object, Database_Query_Update $query, array $many_spec, &$alias = "J", $link_alias = null, $join_type = true, $reverse = false) {
		throw new Exception_Unimplemented(__METHOD__);
	}

	/**
	 *
	 * @param ORM $object
	 * @param array $many_spec
	 * @param string $alias
	 * @param string $reverse
	 */
	final public function has_many_query_default(ORM $object, array $many_spec, $alias = "J", $reverse = false) {
		$query = $many_spec['object']->query_select($alias);
		$this->_has_many_query($object, $query, $many_spec, $alias, null, true, $reverse);
		return $query;
	}

	/**
	 *
	 * @param ORM $object
	 * @param array $many_spec
	 * @param string $alias
	 * @param string $reverse
	 * @return Database_Query_Update
	 */
	final public function has_many_query_update_default(ORM $object, array $many_spec, $alias = "J", $reverse = false) {
		$query = $many_spec['object']->query_update($alias);
		$this->_has_many_query_update($object, $query, $many_spec, $alias, null, true, $reverse);
		return $query;
	}
	private function has_many_query(ORM $object, $member) {
		$many_spec = $this->has_many($this, $member);
		if ($many_spec === null) {
			throw new Exception_Semantics("{method} on non-many column: {member}", array(
				"method" => __METHOD__,
				"member" => $member
			));
		}
		$query = $many_spec['object']->query_select();
		$this->_has_many_query($object, $query, $many_spec, $member);
		return $query;
	}
	final function has_many_object($class) {
		$member = avalue($this->has_many_objects, $class, null);
		if (!$member) {
			return null;
		}
		return $this->has_many($this, $member);
	}

	/**
	 * Returns valid member names for this database table
	 *
	 * @return string ';'-separated list of fields in this database
	 */
	final public function member_names() {
		return array_keys($this->column_types + $this->has_one + $this->has_many);
	}
	final public function column_names() {
		return array_keys($this->column_types + $this->has_one);
	}
	final public function has_many(ORM $object, $member) {
		if (!array_key_exists($member, $this->has_many)) {
			return null;
		}
		$has_many = $this->has_many[$member];
		if (avalue($has_many, '_inited')) {
			return $has_many;
		}
		$this->has_many[$member] = $this->has_many_init($object, $has_many);
		return $this->has_many[$member];
	}

	/**
	 * Generate a query for a member
	 *
	 * @param ORM $this_object
	 * @param string $member
	 * @param ORM $object
	 *        	Returned object class which represents the target object type
	 * @return Database_Query_Select
	 * @throws Exception_Semantics
	 */
	final public function member_query(ORM $this_object, $member, &$object = null) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics($this->class . "::member_query($member) called on non-many member");
		}
		$many_spec = $this->has_many($this_object, $member);
		$query = $this->has_many_query_default($this_object, $many_spec, $member, true);
		$object = $many_spec['object'];
		return $query;
	}

	/**
	 * Generate a query for a member
	 *
	 * @param ORM $this_object
	 * @param string $member
	 * @param ORM $object
	 *        	Returned object class which represents the target object type
	 * @return Database_Query_Update
	 * @throws Exception_Semantics
	 */
	final public function member_query_update(ORM $this_object, $member, &$object = null) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics($this->class . "::member_query($member) called on non-many member");
		}
		$many_spec = $this->has_many($this_object, $member);
		$query = $this->has_many_query_update_default($this_object, $many_spec, $member, true);
		$object = $many_spec['object'];
		return $query;
	}

	/**
	 *
	 * @param unknown $member
	 */
	final public function member_foreign_list(ORM $object, $member) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics(__CLASS__ . "::member_foreign_list($member) called on non-many member");
		}
		$many_spec = $this->has_many($object, $member);
		$query = $this->has_many_query_default($this, $many_spec, $member, true);
		$far_key = $many_spec['far_key'];
		return $query->what("X", $far_key)->to_array(null, "X");
	}
	final public function member_foreign_exists(ORM $object, $member, $id) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics(__CLASS__ . "::member_foreign_exists($member) called on non-many member");
		}
		$many_spec = $this->has_many($object, $member);
		$query = $this->has_many_query_default($object, $many_spec, $member, true);
		$far_key = $many_spec['far_key'];
		$what = "COUNT(" . $this->database()->sql()->column_alias($far_key) . ")";
		return $query->what("*X", $what)->where($far_key, $id)->integer("X") !== 0;
	}
	final public function member_foreign_delete(ORM $object, $member) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics(__CLASS__ . "::membe-r_foreign_delete($member) called on non-many member");
		}
		$many_spec = $this->has_many($object, $member);
		$table = avalue($many_spec, 'table');
		$foreign_key = avalue($many_spec, 'foreign_key', get_class($this));
		if ($table === null) {
			$table = $this->application->object_table_name($many_spec["class"]);
		}
		return array(
			'0-fk_delete-' . $table . '-' . $foreign_key => array(
				'_fk_delete',
				$table,
				$foreign_key
			)
		);
	}
	final public function member_foreign_add(ORM $this_object, $member, ORM $link) {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics(__CLASS__ . "::member_foreign_add($member) called on non-many member");
		}
		$many_spec = $this->has_many($this_object, $member);

		$class = $many_spec['class'];
		if (!$link instanceof $class) {
			throw new Exception_Semantics(get_class($link) . " is not an instanceof $class");
		}
		$table = avalue($many_spec, 'table');
		$foreign_key = $many_spec['foreign_key'];
		if ($table === null) {
			return array(
				'1-fk_store' . $link . '-' . $foreign_key => array(
					'_fk_store',
					$link,
					$foreign_key
				)
			);
		} else {
			$far_key = $many_spec['far_key'];
			return array(
				'1-fk_link_store' . $link . '-' . $foreign_key => array(
					'_fk_link_store',
					$link,
					$table,
					array(
						$far_key => '{Far}',
						$foreign_key => '{Foreign}'
					)
				)
			);
		}
	}

	/**
	 * Ensure our has_many structure has all fields, add implied/default fields here.
	 *
	 * @todo Remove dependencies on "table" use "link_class" instead
	 * @param ORM $object
	 * @param array $has_many
	 * @throws Exception_Semantics
	 * @return array
	 */
	private function has_many_init(ORM $object, array $has_many) {
		$class = $has_many['class'];
		$my_class = $this->class;
		$link_class = avalue($has_many, 'link_class');
		if ($link_class) {
			$this->application->classes->register($link_class);
			$table = $this->application->object_table_name($link_class);
			if (!$table) {
				throw new Exception_Configuration("$link_class::table", "Link class for {class} {link_class} table is empty", array(
					"class" => get_class($object),
					"link_class" => $link_class
				));
			}
			if (array_key_exists("table", $has_many)) {
				$this->application->logger->warning("Key \"table\" is ignored in has many definition: {table}", $has_many);
			}
			$has_many['table'] = $table;
		} else {
			$table = avalue($has_many, 'table');
		}
		if ($this->inherit_options) {
			$object = ORM::cached($class);
		} else {
			$object = $object->orm_factory($class, null, $object->inherit_options());
		}
		if (!$object instanceof ORM) {
			throw new Exception_Semantics("{class} is not an instance of ORM", compact("class"));
		}
		if ($table === true) {
			// Clean up reference
			$table = avalue($object->class_object()->has_many_object($class), 'table');
			if (!is_string($table)) {
				throw new Exception_Semantics("{my_class} references table in {class}, but no table found for have_many", compact("my_class", "class"));
			}
			$has_many['table'] = $table;
		}
		if (!array_key_exists('foreign_key', $has_many)) {
			$has_many['foreign_key'] = $my_class;
		}
		if (!array_key_exists('far_key', $has_many)) {
			$has_many['far_key'] = $table ? $class : $object->id_column();
		}
		$has_many['object'] = $object;
		$has_many['_inited'] = true;
		return $has_many;
	}
	private function has_many_object_join($class, $join_column) {
		$spec = $this->has_many_object($class);
		if (!$spec) {
			return null;
		}
		$foreign_key = null;
		extract($spec, EXTR_IF_EXISTS);
		return array(
			$join_column => $foreign_key
		);
	}

	/**
	 * Retrieve the database for this object
	 *
	 * @return Database
	 */
	final public function database(Database $set = null) {
		if ($set !== null) {
			$this->database = $set;
			$this->database_name = $set->code_name();
			$set->application->orm_module()->clear_cache($this->class);
			return $this;
		}
		if ($this->database instanceof Database) {
			return $this->database;
		}
		return $this->database = $this->application->database_factory($this->database_name);
	}

	/**
	 * Get the schema for this object
	 *
	 * @param string $sql
	 *        	Optional SQL
	 * @return ORM_Schema
	 */
	final private function _database_schema(ORM $object = null, $sql = null) {
		try {
			list($namespace, $class) = PHP::parse_namespace_class($this->class);
			if ($namespace) {
				$namespace .= "\\";
			}
			return $this->application->objects->factory($namespace . "Schema_" . $class, $this, $object);
		} catch (Exception_Class_NotFound $e) {
			$schema = new ORM_Schema_File($this, $object, $sql);
			if ($schema->exists() || $schema->has_sql()) {
				return $schema;
			}
			$this->application->logger->warning("Can not find schema for {class} in {searches}, or schema object {exception}", array(
				"class" => $this->class,
				"searches" => "\n" . implode("\n\t", $schema->searches()) . "\n",
				"exception" => $e
			));
			return null;
		} catch (Exception $e) {
			$this->application->hooks->call("exception", $e);
			$this->application->logger->error("Schema error for " . $this->class . " (" . get_class($e) . ": " . $e->getMessage() . ")");
			return null;
		}
	}

	/**
	 *
	 * @return ORM_Schema
	 */
	final public function database_schema(ORM $object = null) {
		$result = $object ? $object->schema() : $this->schema();
		if ($result instanceof ORM_Schema) {
			return $result;
		} else if (is_array($result)) {
			return $this->_database_schema($object, implode(";\n", $result));
		}
		if ($result === null) {
			return $result;
		}
		return $this->_database_schema($object, $result);
	}

	/**
	 * Override this in subclasses to provide an alternate schema
	 *
	 * @return ORM_Schema
	 */
	public function schema(ORM $object) {
		return $this->_database_schema($object);
	}

	/**
	 * Member defaults
	 *
	 * @return array
	 */
	private function _column_defaults() {
		if (!$this->column_defaults) {
			$this->column_defaults = array();
		}
		$column_types = $this->column_types;
		foreach (array_keys($this->column_types) as $column) {
			if (array_key_exists($column, $this->column_defaults)) {
				continue;
			}
			$this->member_default($column, avalue($column_types, $column), $this->column_defaults);
		}
		return $this->column_defaults;
	}

	/**
	 * Take a database result and convert it into the internal data array
	 *
	 * @param array $data
	 * @return array
	 */
	final public function from_database(ORM $object, array $data) {
		$result = array();
		$column_types = $this->column_types;
		$data = $object->sql()->from_database($object, $data);
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->member_from_database($object, $column, $column_types[$column], $data);
		}
		return $data;
	}

	/**
	 * Take an external array and convert it into the internal data array
	 *
	 * @param array $data
	 * @return array
	 */
	final public function from_array(ORM $object, array $data) {
		$column_types = $this->column_types;
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->member_from_array($object, $column, $column_types[$column], $data);
		}
		return $data;
	}

	/**
	 * Take internal data and convert into a form consumable by database calls
	 *
	 * @param array $data
	 * @param boolean $insert
	 *        	This is an insert (vs update)
	 * @return array
	 */
	final public function to_database(ORM $object, array $data, $insert = false) {
		$data = $object->sql()->to_database($object, $data, $insert);
		$column_types = $this->column_types;
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->member_to_database($object, $column, $column_types[$column], $data, $insert);
		}
		return $data;
	}

	/**
	 *
	 * @param unknown $column
	 * @param unknown $type
	 * @param array $data
	 */
	private function member_default($column, $type, array &$data) {
		switch ($type) {
			case self::type_polymorph:
				$data[$column] = '';
				break;
			case self::type_created:
			case self::type_modified:
				$data[$column] = 'now';
				break;
			default :
				$data[$column] = null;
				break;
		}
	}

	/**
	 * Generate the desired zesk ORM class name to instantiate this object
	 *
	 * Override in subclasses to get different polymorphic behavior.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function polymorphic_class_generate($value) {
		return strtolower($this->polymorphic . "_" . $value);
	}

	/**
	 * Convert the existing ORM class name to the preferred code used in the database
	 *
	 * Override in subclasses to get different polymorphic behavior.
	 *
	 * @param ORM $object
	 * @param string $column
	 *        	Column which is generating this value
	 * @return string
	 */
	protected function polymorphic_class_parse(ORM $object, $column) {
		$class = str::unprefix(get_class($object), array(
			$this->polymorphic . "_",
			get_class($object)
		), true);
		return strtolower($class);
	}

	/**
	 * Convert member from database to internal format.
	 * Result value is hints to calling function about additional properties to set in the object.
	 *
	 * Currently passes back polymorphic_leaf class.
	 *
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @throws Exception_Semantics
	 * @return multitype:string
	 */
	private function member_from_database(ORM $object, $column, $type, array &$data) {
		$result = array();
		$v = $data[$column];
		switch ($type) {
			case self::type_real:
			case self::type_float:
			case self::type_double:
			case self::type_decimal:
				if ($v === null) {
					break;
				}
				$data[$column] = to_double($v);
				break;
			case self::type_text:
			case self::type_string:
				if ($v === null) {
					break;
				}
				$data[$column] = strval($v);
				break;
			case self::type_hex:
			case self::type_hex32:
				$data[$column] = Hexadecimal::encode($v);
				break;
			case self::type_object:
				if (empty($v)) {
					$data[$column] = null;
				} else {
					$data[$column] = $v;
				}
				break;
			case self::type_id:
			case self::type_integer:
				$data[$column] = to_integer($v);
				break;
			case self::type_boolean:
				$data[$column] = to_bool($v);
				break;
			case self::type_serialize:
				$data[$column] = $result = empty($v) ? null : @unserialize($v);
				if ($result === false && $v !== 'b:0;') {
					$this->application->logger->error("unserialize of {n} bytes failed: {data}", array(
						"n" => strlen($v),
						"data" => substr($v, 0, 100)
					));
				}
				break;
			case self::type_json:
				try {
					$data[$column] = empty($v) ? null : JSON::decode($v);
				} catch (Exception_Parse $e) {
					$this->application->logger->error("Unable to parse JSON in {class}->{column} {json}", array(
						"class" => get_class($object),
						"column" => $column,
						"json" => $v
					));
				}
				break;
			case self::type_created:
			case self::type_modified:
			case self::type_timestamp:
			case self::type_datetime:
				$data[$column] = $v === '0000-00-00 00:00:00' || empty($v) ? null : Timestamp::factory($v);
				break;
			case self::type_date:
				$data[$column] = $v === '0000-00-00' || empty($v) ? null : Date::factory($v);
				break;
			case self::type_time:
				$data[$column] = empty($v) ? null : Time::factory($v);
				break;
			case self::type_ip:
			case self::type_ip4:
				$data[$column] = $v === null ? null : IPv4::from_integer($v);
				break;
			case self::type_polymorph:
				if ($v) {
					if ($this->polymorphic === null) {
						$this->application->logger->error("{class} has polymorph member {column} but is not polymorphic", array(
							'class' => get_class($this),
							'column' => $column
						));
						break;
					}
					$full_class = $this->polymorphic_class_generate($v);
					// 					$this->application->logger->debug("Setting object {class} polymorphic to {full_class} (polymorphic={polymorphic}, v={v})", array(
					// 						"class" => get_class($object),
					// 						"polymorphic" => $this->polymorphic,
					// 						"v" => $v,
					// 						"full_class" => $full_class
					// 					));
					$object->polymorphic($full_class);
				}
				break;
			default :
				throw new Exception_Semantics("Invalid column type $type");
		}
	}

	/**
	 * Convert member into to internal format.
	 * Result value is hints to calling function about additional properties to set in the object.
	 *
	 * Currently may set polymorphic_leaf class.
	 *
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @throws Exception_Semantics
	 * @return multitype:string
	 */
	private function member_from_array(ORM $object, $column, $type, array &$data) {
		switch ($type) {
			case self::type_hex:
			case self::type_hex32:
			case self::type_serialize:
			case self::type_json:
				break;
			case self::type_ip:
			case self::type_ip4:
				if (is_integer($data[$column])) {
					$data[$column] = IPv4::from_integer($data[$column]);
				}
				break;
			default :
				$this->member_from_database($object, $column, $type, $data);
				break;
		}
	}

	/**
	 * Return the SQL version for now
	 *
	 * @param ORM $object
	 * @return string
	 */
	private function sql_now(ORM $object) {
		$generator = $object->database()->sql();
		return $this->utc_timestamps ? $generator->now_utc() : $generator->now();
	}

	/**
	 * Retrieve members matching a given type
	 *
	 * @param string $type
	 * @return array List of matching members as keys, type as a value
	 */
	public function members_of_type($type) {
		return ArrayTools::filter_value($this->column_types, $type, true);
	}

	/**
	 * Convert a member into format suitable for the database
	 *
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @param string $insert
	 *        	- If this is an insertion
	 * @throws Exception_Semantics
	 */
	final public function member_to_database(ORM $object, $column, $type, array &$data, $insert = false) {
		if (!array_key_exists($column, $data)) {
			var_dump($column, $data);
			backtrace();
		}
		$gen = $this->database()->sql();
		$v = $data[$column];
		switch ($type) {
			case self::type_polymorph:
				$data[$column] = $this->polymorphic_class_parse($object, $column);
				break;
			case self::type_real:
			case self::type_float:
			case self::type_double:
			case self::type_decimal:
				$data[$column] = $v === null || $v === "" ? null : doubleval($v);
				break;
			case self::type_text:
			case self::type_string:
				$data[$column] = $v === null ? null : strval($v);
				break;
			case self::type_object:
				$data[$column] = $v === null ? null : ORM::mixed_to_id($v);
				break;
			case self::type_crc32:
				if (isset($this->crc_column)) {
					$data["*$column"] = "CRC32(" . $this->database()->quote_name($object->member($this->crc_column)) . ")";
				}
				unset($data[$column]);
				break;
			case self::type_hex:
			case self::type_hex32:
				$data[$column] = Hexadecimal::decode($v);
				break;
			case self::type_id:
				$data[$column] = to_integer($v, $v);
				break;
			case self::type_integer:
				$data[$column] = to_integer($v, $v);
				break;
			case self::type_byte:
				$data[$column] = to_integer($v, $v) % 255;
				break;
			case self::type_boolean:
				$data[$column] = to_bool($v) ? 1 : 0;
				break;
			case self::type_serialize:
				$data[$column] = serialize($v);
				break;
			case self::type_json:
				$data[$column] = JSON::encode($v);
				break;
			case self::type_created:
				unset($data[$column]);
				if ($insert) {
					$data["*$column"] = $this->sql_now($object);
				}
				break;
			case self::type_modified:
				unset($data[$column]);
				$data["*$column"] = $this->sql_now($object);
				break;
			case self::type_timestamp:
			case self::type_datetime:
				if (empty($v)) {
					$data[$column] = null;
				} else if ($v === "now") {
					unset($data[$column]);
					$data["*$column"] = $this->sql_now($object);
				} else if ($v instanceof Timestamp) {
					$data[$column] = $v->sql();
				} else if (is_numeric($v)) {
					$data[$column] = gmdate('Y-m-d H:i:s', $v);
				}
				break;
			case self::type_date:
				if (empty($v)) {
					$data[$column] = null;
				} else if ($v === "now") {
					unset($data[$column]);
					$data["*$column"] = $this->sql_now($object);
				} else if ($v instanceof Temporal) {
					$data[$column] = $v->format('{YYYY}-{MM}-{DD}');
				} else if (is_numeric($v)) {
					$data[$column] = gmdate('Y-m-d', $v);
				}
				break;
			case self::type_time:
				if (empty($v)) {
					$data[$column] = null;
				} else if ($v === "now") {
					unset($data[$column]);
					$data["*$column"] = $this->sql_now($object);
				} else if ($v instanceof Temporal) {
					$data[$column] = $v->format('{hh}:{mm}:{ss}');
				} else if (is_numeric($v)) {
					$data[$column] = gmdate('H:i:s', $v);
				}
				break;
			case self::type_ip4:
			case self::type_ip:
				if ($v === null) {
					$data[$column] = "NULL";
					return;
				}
				$data["*$column"] = $gen->function_ip2long($gen->quote_text($v));
				unset($data[$column]);
				break;
			default :
				throw new Exception_Semantics("Invalid column type $type");
		}
	}

	/**
	 * Guess column types
	 *
	 * Updates internal $this->column_types
	 */
	private function imply_column_types() {
		$data_type = $this->database()->data_type();
		foreach ($this->table_columns as $name => $sql_type) {
			$this->column_types[$name] = $data_type->native_type_to_data_type($sql_type);
		}
	}

	/**
	 * Name/value pairs used to generate the schema for this object
	 *
	 * @return array
	 */
	public function schema_map() {
		// Some of these are for MySQL only. Good/bad? TODO
		return $this->option_array("schema_map") + array(
			'name' => $this->name,
			'code_name' => $this->code_name,
			'table' => $this->table,
			"extra_keys" => "",
			"auto_increment" => $this->auto_column ? "AUTO_INCREMENT" : "",
			"primary_keys" => implode(",", $this->primary_keys)
		);
	}

	/**
	 * Does this object have the following column?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function has_column($name) {
		return array_key_exists($name, $this->column_types);
	}

	/**
	 * Class variables
	 */
	public function variables() {
		return array(
			"name" => $class_name = __($this->name),
			"names" => $this->application->locale->plural($class_name),
			"name_column" => $this->name_column,
			"id_column" => $this->id_column,
			"primary_keys" => $this->primary_keys,
			"class" => get_class($this)
		);
	}
}
