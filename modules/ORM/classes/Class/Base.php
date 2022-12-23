<?php
declare(strict_types=1);

/**
 * Class abstraction for ORM Relational Map
 *
 * This is where the magic happens for ORMs
 *
 * Copyright &copy; 2022, Market Acumen, Inc.
 * @author kent
 * @see ORMBase
 */

namespace zesk\ORM;

use Psr\Cache\InvalidArgumentException;
use zesk\Database_Exception_SQL;
use zesk\Exception as BaseException;
use zesk\Hookable;
use zesk\ArrayTools;
use zesk\StringTools;
use zesk\PHP;
use zesk\Hooks;
use zesk\Timestamp;
use zesk\Date;
use zesk\Time;
use zesk\IPv4;
use zesk\JSON;
use zesk\Hexadecimal;
use zesk\Temporal;

use zesk\Application;

use zesk\ORM\Schema as Schema;
use zesk\Database;

use zesk\Exception_Configuration;
use zesk\Exception_Unimplemented;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Exception_Convert;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
use zesk\Exception_Class_NotFound;

/**
 *
 * @see ORMBase
 */
class Class_Base extends Hookable {
	public const HAS_MANY_INITIALIZED = '*init*';

	/**
	 *
	 */
	public const ID_AUTOASSIGN = '*';

	/**
	 * For ID columns
	 *
	 * @var string
	 */
	public const TYPE_ID = 'id';

	/**
	 * Plain old text data in the database
	 *
	 * @var string
	 */
	public const type_text = 'text';

	/**
	 * Plain old text data in the database (varchar)
	 *
	 * @var string
	 */
	public const TYPE_STRING = 'string';

	/**
	 * This column serves as text data for polymorphic objects
	 *
	 * On store, saves current object class polymorphic name
	 * On loading, creates into new object
	 *
	 * @var string
	 */
	public const type_polymorph = 'polymorph';

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const TYPE_OBJECT = self::type_orm;

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const type_orm = 'orm';

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	public const type_created = 'created';

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	public const TYPE_MODIFIED = 'modified';

	/**
	 * String information called using serialize/unserialize.
	 * Column should be a blob (not text)
	 *
	 * @var string
	 */
	public const TYPE_SERIALIZE = 'serialize';

	/**
	 * Convert data to/from a JSON string in the database.
	 * Column should be a blob (not text)
	 *
	 * @var string
	 */
	public const type_json = 'json';

	/**
	 * Convert data to/from an integer
	 *
	 * @var string
	 */
	public const TYPE_INTEGER = 'integer';

	/**
	 * Database string (char)
	 *
	 * @var string
	 */
	public const type_character = 'character';

	/**
	 * Single-precision floating point number
	 *
	 * @var string
	 */
	public const type_real = 'real';

	/**
	 *
	 * @var string
	 */
	public const type_float = 'float';

	/**
	 *
	 * @var string
	 */
	public const TYPE_FLOAT = 'double';

	/**
	 *
	 * @var string
	 */
	public const type_decimal = 'decimal';

	/**
	 *
	 * @var string
	 */
	public const type_byte = 'byte';

	/**
	 *
	 * @var string
	 */
	public const type_binary = 'binary';

	/**
	 *
	 * @var string
	 */
	public const type_boolean = 'boolean';

	/**
	 *
	 * @var string
	 */
	public const TYPE_TIMESTAMP = 'timestamp';

	/**
	 *
	 * @var string
	 */
	public const type_datetime = 'datetime';

	/**
	 *
	 * @var string
	 */
	public const type_date = 'date';

	/**
	 *
	 * @var string
	 */
	public const type_time = 'time';

	/**
	 *
	 * @var string
	 */
	public const type_ip = 'ip';

	/**
	 *
	 * @var string
	 */
	public const TYPE_IP4 = 'ip4';

	/**
	 *
	 * @var string
	 */
	public const type_crc32 = 'crc32';

	/**
	 *
	 * @var string
	 */
	public const type_hex32 = 'hex';

	/**
	 *
	 * @var string
	 */
	public const type_hex = 'hex';

	/**
	 * Application class associated with this Class_Base
	 *
	 * @var string
	 */
	public string $application_class;

	/**
	 * PHP Class which created this (type ORM)
	 *
	 * @var string
	 */
	public string $class;

	/**
	 * String name of the database to use
	 *
	 * @var string
	 */
	public string $database_name = '';

	/**
	 * Name of the ORM which should share this database with.
	 * String must contain namespace prefix, if any.
	 *
	 * Allow objects to be grouped into a database (by module) or functionality, for example.
	 *
	 * @var string
	 */
	protected string $database_group = '';

	/**
	 * Database name where this object resides.
	 * If not specified, the default database.
	 * <code>
	 * protected $database = "tracker";
	 * </code>
	 *
	 * @var ?Database
	 */
	private ?Database $database = null;

	/**
	 * Database table name
	 * <code>
	 * protected $table = "TArticleComment";
	 * </code>
	 *
	 * @var string
	 */
	public string $table = '';

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
	public string $name = '';

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
	public array $members = [];

	/**
	 * Specify the base polymorphic class here, or true if it uses a method
	 *
	 * @var mixed
	 */
	public string|bool $polymorphic = false;

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
	public bool $polymorphic_inherit_class = true;

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
	public string $code_name = '';

	/**
	 * <code>
	 * protected $schema_file = 'MyORM.sql';
	 * </code>
	 * File which contains the schema for this object, found in:
	 * Schema_File::template_schema_paths()
	 * If not specified, it's get_class($this) . ".sql"
	 *
	 * @var string
	 */
	public string $schema_file = '';

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
	public array $columns = [];

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
	public array $column_types = [];

	/**
	 * Whether to dynamically load the object columns from the database
	 *
	 * @var boolean
	 */
	public bool $load_database_columns = false;

	/**
	 * Member defaults: fill in only defaults you want to set
	 */
	public array $column_defaults = [];

	/**
	 * Which column to use in a CRC checksum
	 *
	 * @var string
	 */
	public string $crc_column = '';

	/**
	 * The default column for displaying this object's name
	 *
	 * @var string
	 */
	public string $name_column = '';

	/**
	 * Name of the column which uniquely identifies this object in the table.
	 * Default is "id"
	 *
	 * @var string
	 */
	public string $id_column = self::ID_AUTOASSIGN;

	/**
	 * Name of the columns which uniquely identifies this object in the table.
	 *
	 * @var array
	 */
	public array $primary_keys = [];

	/**
	 * Name of the column which is automatically incremented upon saves
	 * Set to the blank string if no column exists.
	 *
	 * @var string
	 */
	public string $auto_column = '';

	/**
	 * List of columns used by default to look up an object for a match. Used in `exists()`
	 *
	 * If empty, set to $this->primary_keys
	 *
	 * @var array
	 */
	public array $find_keys = [];

	/**
	 * When finding, order results this way and retrieve the first item
	 *
	 * @var array
	 */
	public array $find_order_by = [];

	/**
	 * Add this to the where clause when searching for duplicates
	 *
	 * @var array
	 */
	public array $duplicate_where = [];

	/**
	 * List of columns which are used to determine if a duplicate exists in the database.
	 *
	 * If empty, no checking occurs prior to doing `store()` so errors will be thrown from
	 * the database, possibly.
	 *
	 * If this value is set, a SELECT occurs within `store()` to determine if a collision will
	 * occur prior to INSERT.
	 *
	 * Be aware of the costs of setting this value prior to doing so, as it is incurred on a
	 * per-store basis for this object thereafter.
	 *
	 * @var array
	 */
	public array $duplicate_keys = [];

	/**
	 * Use UTC timestamps for Created and Modified columns.
	 * Default value is set to boolean option "utc_timestamps", then
	 * global "ORM::utc_timestamps", then true.
	 *
	 * @var boolean
	 */
	public bool $utc_timestamps = true;

	/**
	 * Whether this object has its columns determined programmatically.
	 * Set by ORM class, read-only by subclasses
	 *
	 * @var boolean
	 */
	public bool $dynamic_columns = false;

	/**
	 * Function to call to get a field as implemented in ORM subclass (not Class_Base subclass!)
	 * Method should be identical to __get prototype.
	 * (Allows reuse.)
	 *
	 * @var array
	 */
	public array $getters = [];

	/**
	 * Function to call to set a field as implemented in ORM subclass (not Class_Base subclass!)
	 * Method should be identical to __set prototype.
	 * (Allows reuse.)
	 *
	 * @var array
	 */
	public array $setters = [];

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
	public array $has_many = [];

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
	 * public array $has_one = array("field" => "*other_field");
	 * </code>
	 *
	 * Will look at string member "other_field" to determine the class to use for "field".
	 *
	 * Another example:
	 *
	 * <code>
	 * public array $has_one = array(
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
	public array $has_one = [];

	/**
	 * $this->has_one flipped with identical columns as arrays
	 *
	 * @var array
	 */
	public array $has_one_flip = [];

	/**
	 * List of options in this object which should be passed to sub-objects
	 * Can be an array or a semicolon separated list.
	 *
	 * @var array
	 */
	public array $inherit_options = [];

	/**
	 * The deleted column to support soft deletions
	 *
	 * @var string
	 */
	public string $column_deleted = '';

	/*
	 *  Lookup list of class => member
	 */
	private array $has_many_objects = [];

	/*
	 *  Cached table columns
	 */
	private array $table_columns = [];

	/**
	 * Class cache
	 *
	 * @var Class_Base[]
	 */
	public static array $classes = [];

	/**
	 * Class cache
	 *
	 * @var boolean
	 */
	public static bool $classes_dirty = false;

	/**
	 * List of deferrable class linkages
	 *
	 * @var array
	 */
	public static array $defer_class_links = [];

	/**
	 * Register all zesk hooks.
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(Hooks::HOOK_RESET, function () use ($application): void {
			self::_hook_reset($application);
		});
	}

	/**
	 * Reset our internal caches
	 *
	 * @param Application $application
	 */
	public static function _hook_reset(Application $application): void {
		assert($application->isConfigured());
		self::$classes = [];
		self::$classes_dirty = false;
		self::$defer_class_links = [];
	}

	/**
	 * Handle namespace objects intelligently and preserve namespace (\ group), prefixing class name
	 * (_ group)
	 *
	 * @inline_test assertEqual(Class_Base::object_to_class('zesk\Dude'), 'zesk\Class_Dude');
	 * @inline_test assertEqual(Class_Base::object_to_class('a\b\c\d\e\f\g\Dude'),
	 * 'a\b\c\d\e\f\g\Class_Dude');
	 *
	 * @param string $classname
	 * @return string
	 */
	public static function objectToClass(string $classname): string {
		[$namespace, $suffix] = reversePair($classname, '\\', '', $classname, 'left');
		return $namespace . 'Class_' . $suffix;
	}

	/**
	 * Create a new class instance - should only be called from ORM
	 *
	 * @param ORMBase $object
	 * @param array $options
	 * @param string|null $class
	 * @return Class_Base
	 * @throws Exception_Class_NotFound
	 */
	public static function instance(ORMBase $object, array $options = [], string $class = null): Class_Base {
		if ($class === null) {
			$class = $object::class;
		}
		$application = $object->application;
		$lowClass = strtolower($class);

		if (array_key_exists($lowClass, self::$classes)) {
			return self::$classes[$lowClass];
		}
		$class_class = self::objectToClass($class);
		$instance = self::$classes[$lowClass] = $application->objects->factory($class_class, $object, $options);
		assert($instance instanceof Class_Base);
		self::$classes_dirty = true;
		return $instance;
	}

	/**
	 *
	 * @ignore
	 *
	 */
	public function __sleep() {
		return array_merge([
			'application_class', 'class', 'database_name', 'table', 'name', 'members', 'polymorphic',
			'polymorphic_inherit_class', 'code_name', 'schema_file', 'columns', 'column_types', 'load_database_columns',
			'column_defaults', 'crc_column', 'name_column', 'id_column', 'primary_keys', 'auto_column', 'find_keys',
			'find_order_by', 'duplicate_where', 'duplicate_keys', 'utc_timestamps', 'dynamic_columns', 'getters',
			'setters', 'has_many', 'has_one', 'inherit_options', 'column_deleted', 'cache_column_names',
		], parent::__sleep());
	}

	/**
	 *
	 * @see wakeup
	 */
	public function __wakeup(): void {
		$this->application = __wakeup_application();
		$this->application->hooks->registerClass($this->class);
	}

	/**
	 * Lazy link classes together with has_many functionality
	 *
	 * @param string $class
	 * @param string $member
	 *            Member name to use for iteration, etc.
	 * @param array $many_spec
	 *            Many specification
	 * @throws Exception_Key
	 */
	public static function linkMany(string $class, string $member, array $many_spec): void {
		if (!array_key_exists('class', $many_spec)) {
			throw new Exception_Key('many_spec for class {class} must contain key \'class\' for member {member}', compact('class', 'member'));
		}
		$lowClass = strtolower($class);
		if (array_key_exists($lowClass, self::$classes)) {
			$class = self::$classes[$lowClass];
			$class->_addMany($member, $many_spec);
		} else {
			if (isset(self::$defer_class_links[$lowClass][$member])) {
				throw new Exception_Key('Double link_many added for {class} {member}', compact('class', 'member'));
			}
			self::$defer_class_links[$lowClass][$member] = $many_spec;
		}
	}

	/**
	 * When registering the object, add deferred
	 *
	 * @param string $class
	 * @return void
	 * @throws Exception_Key
	 */
	private function _addDeferLinkMany(string $class): void {
		if (count(self::$defer_class_links) === 0) {
			return;
		}
		foreach ($this->application->classes->hierarchy($class) as $parent_class) {
			$lowClass = strtolower($parent_class);
			if (array_key_exists($lowClass, self::$defer_class_links)) {
				foreach (self::$defer_class_links[$lowClass] as $member => $many_spec) {
					$this->_addMany($member, $many_spec);
				}
				// No delete for now: Do we want to allow multiple links across many subclasses? Probably.
				//unset(self::$defer_class_links[$lowClass]);
			}
		}
	}

	/**
	 * Add a class to our has_many_objects lookup
	 *
	 * @param string $class
	 * @param string $member
	 * @param bool $first
	 * @return void
	 */
	private function addHasManyObject(string $class, string $member, bool $first = false): void {
		$class = $this->application->objects->resolve($class);
		if ($first) {
			ArrayTools::prepend($this->has_many_objects, $class, $member);
		} else {
			ArrayTools::append($this->has_many_objects, $class, $member);
		}
	}

	/**
	 * Add a many member
	 *
	 * @param string $member
	 * @param array $many_spec
	 * @return self
	 * @throws Exception_Key
	 */
	protected function _addMany(string $member, array $many_spec): self {
		if (!array_key_exists('class', $many_spec)) {
			throw new Exception_Key('many_spec for class {class} must contain key \'class\' for member {member}', [
				'class' => get_class($this), 'member' => $member,
			]);
		}
		$this->addHasManyObject($many_spec['class'], $member, toBool($many_spec['default'] ?? false));
		$this->has_many[$member] = map($many_spec, ['table' => $this->table, ]);
		return $this;
	}

	public const OPTION_NAME = 'name';

	public const OPTION_TABLE = 'table';

	public const OPTION_TABLE_PREFIX = 'table_prefix';

	public const OPTION_ID_COLUMN_DEFAULT = 'id_column_default';

	public const DEFAULT_OPTION_ID_COLUMN_DEFAULT = 'id';

	/**
	 * Constructor
	 * @param ORMBase $object
	 * @param array $options
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented|Exception_Class_NotFound
	 */
	public function __construct(ORMBase $object, array $options = []) {
		$app = $object->application;
		parent::__construct($app, $options);
		$this->inheritConfiguration();
		// Handle polymorphic classes - create correct Class and use correct base class
		$this->class = $object->class_orm_name();

		$this->configure($object);
		// In case configure changes it
		$this_class = $this->class;
		if ($this->code_name === '') {
			$this->code_name = StringTools::reverseRight($this_class, '\\');
		}
		if ($this->name === '') {
			$this->name = $this->option(self::OPTION_NAME, $this_class);
		}
		if ($this->table === '') {
			$this->table = $this->option(self::OPTION_TABLE, $object->option(self::OPTION_TABLE, ''));
			if ($this->table === '') {
				$prefix = $this->option(self::OPTION_TABLE_PREFIX, $object->option(self::OPTION_TABLE_PREFIX, ''));
				$this->table = $prefix . $this->code_name;
			}
		}
		/* Automatic promotion here of primary_keys should be avoided - id_column should probably just be internal */
		if (count($this->primary_keys) > 0) {
			if (count($this->primary_keys) === 1) {
				$this->id_column = $this->primary_keys[0];
			} elseif ($this->id_column === self::ID_AUTOASSIGN) {
				$this->id_column = '';
			}
		} elseif ($this->id_column === self::ID_AUTOASSIGN) {
			$this->id_column = $this->option(self::OPTION_ID_COLUMN_DEFAULT, self::DEFAULT_OPTION_ID_COLUMN_DEFAULT);
			if ($this->id_column) {
				assert(count($this->primary_keys) === 0);
				$this->primary_keys = [$this->id_column, ];
			}
		} elseif ($this->id_column === '') {
			$this->primary_keys = [];
			$this->id_column = '';
		} else {
			$this->primary_keys = [$this->id_column, ];
		}

		if ($this->auto_column === '') {
			$auto_type = $this->column_types[$this->id_column] ?? null;
			$this->auto_column = ($auto_type === null || $auto_type === self::TYPE_ID) ? $this->id_column : '';
		}
		if (empty($this->find_keys)) {
			$this->find_keys = $this->primary_keys;
		}
		if (empty($this->duplicate_keys)) {
			$this->duplicate_keys = [];
		}
		$this->_addDeferLinkMany($this_class);
		if (!empty($this->has_many)) {
			foreach ($this->has_many as $member => $many_spec) {
				if (!is_array($many_spec)) {
					throw new Exception_Semantics('many_spec for class {class} must have array value for member {member}', [
						'class' => $this_class, 'member' => $member,
					]);
				}
				if (!array_key_exists('class', $many_spec)) {
					throw new Exception_Semantics('many_spec for class {class} must contain key \'class\' for member {member}', [
						'class' => $this_class, 'member' => $member,
					]);
				}
				$this->addHasManyObject($many_spec['class'], $member, toBool($many_spec['default'] ?? false));
			}
			$this->has_many = map($this->has_many, ['table' => $this->table, ]);
		}
		if (!empty($this->has_one)) {
			$this->has_one_flip = [];
			foreach ($this->has_one as $member => $class) {
				if ($class[0] !== '*') {
					$this->has_one[$member] = $class = $app->objects->resolve($class);
					ArrayTools::append($this->has_one_flip, $class, $member);
				}
				if (isset($this->column_types[$member]) && $this->column_types[$member] !== self::TYPE_OBJECT) {
					$this->application->logger->warning('Class {class} column {member} type is not {object} and will be overwritten: {type}', [
						'class' => get_class($this), 'member' => $member, 'object' => self::TYPE_OBJECT,
						'type' => $this->column_types[$member],
					]);
				}
				$this->column_types[$member] = self::TYPE_OBJECT;
			}
		}
		if (count($this->column_types) === 0) {
			$this->dynamic_columns = true;
		}
		$this->initialize_database($object);
		$this->init_columns();
		$this->_columnDefaults();
		$this->initialize();
		if (count($this->column_types) === 0 && count($this->table_columns) > 0) {
			$this->implyColumnTypes();
		}

		$this->application->hooks->registerClass($this->class);
	}

	/**
	 * @param ORMBase $object
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_ORMNotFound
	 */
	protected function initialize_database(ORMBase $object): void {
		if (!empty($this->database_group) && $this->database_group !== $this->class) {
			if ($this->database_name !== '') {
				$this->application->logger->warning('database_name value {database_name} is ignored, using database_group {database_group}', [
					'database_name' => $this->database_name, 'database_group' => $this->database_group,
				]);
			}
			$this->database_name = $this->application->ormRegistry($this->database_group)->databaseName();
		}
		if ($this->database_name !== '') {
			if ($this->database_name === $object->databaseName()) {
				$this->database = $object->database();
			} else {
				$this->database = $this->application->database_registry($this->database_name);
			}
		}
		if ($this->database === null) {
			$this->database = $object->database();
		}
	}

	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 */
	protected function configure(ORMBase $object): void {
		// pass
	}

	/**
	 * Overwrite this in subclasses to change stuff upon instantiation
	 */
	protected function initialize(): void {
	}

	/**
	 * Load columns from database
	 *
	 * @return boolean
	 * @throws Exception_Unimplemented
	 */
	final public function init_columns(): bool {
		if (!$this->load_database_columns && count($this->column_types) > 0) {
			if (!count($this->primary_keys)) {
				throw new Exception_Unimplemented('No support for database synchronized column without primary_keys defined {class}', ['class' => get_class($this)]);
			}
			return true;
		}
		if (!$this->load_columns()) {
			return false;
		}
		return true;
	}

	/**
	 * Load database columns from database/cache
	 *
	 * @return boolean
	 */
	private function load_columns(): bool {
		if (count($this->table_columns)) {
			return false;
		}
		$return = false;
		$pool = $this->application->cache;
		$table = $this->table;

		try {
			$cache = $pool->getItem(__CLASS__ . "::column_cache::$table");
			if ($cache->isHit()) {
				$this->table_columns = $cache->get();
				return true;
			}
			$return = true;
		} catch (InvalidArgumentException) {
			$cache = null;
		}

		try {
			$columns = $this->database()->tableColumns($this->table);
			$this->table_columns = [];
			foreach ($columns as $object) {
				$name = $object->name();
				$this->table_columns[$name] = $object->sql_type();
			}
			if ($cache) {
				$pool->saveDeferred($cache->set($this->table_columns));
			}
		} catch (BaseException $e) {
			$this->application->hooks->call('exception', $e);
			if ($cache) {
				try {
					$pool->deleteItem($cache->getKey());
				} catch (InvalidArgumentException) {
				}
			}
			$this->table_columns = [];
		}
		return $return;
	}

	/**
	 * Given a class $class, determine the default path to the class
	 *
	 * @param $class string
	 * @return string
	 * @throws Exception_ORMNotFound
	 */
	final public function link_default_path_to(string $class): string {
		$fields = $this->has_one_flip[$class] ?? null;
		if (is_array($fields)) {
			return $fields[0];
		}
		if (is_string($fields)) {
			return $fields;
		}
		$has_many = $this->has_many_objects[$class] ?? null;
		if ($has_many === null) {
			throw new Exception_ORMNotFound($class, 'No has many');
		}
		if (is_array($has_many)) {
			$has_many = $has_many[0];
		}
		return $has_many;
	}

	/**
	 * @param ORMBase $object
	 * @param string $segment
	 * @return ORMBase
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	private function _findNextObject(ORMBase $object, string $segment): ORMBase {
		if (array_key_exists($segment, $this->has_one)) {
			$to_class = $this->has_one[$segment];
			if ($to_class[0] === '*') {
				$to_class = $object->member(substr($to_class, 1));
			}
			return $this->application->ormRegistry($to_class);
		}

		throw new Exception_ORMNotFound($segment, 'Next segment has no object');
	}

	/**
	 * @param ORMBase $object
	 * @param Database_Query_Select $query
	 * @param array $link_state
	 * @return Database_Query_Select
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics|Exception_ORMNotFound
	 */
	final public function linkWalk(ORMBase $object, Database_Query_Select $query, array $link_state = []): Database_Query_Select {
		$generator = $this->database()->sql();
		$path = $link_state['path'] ?? '';
		if ($path === '') {
			throw new Exception_Semantics($this->class . '::link_walk: No path in ' . serialize($link_state));
		}
		[$segment, $path] = pair($path, '.', $path);
		$join_type = $link_state['type'] ?? (($link_state['require'] ?? true) ? 'INNER' : 'LEFT OUTER');

		$has_alias = $query->findAlias($segment);
		if ($has_alias) {
			$to_object = $this->application->ormRegistry($has_alias);

			$link_state['path_walked'][] = $segment;
			$link_state['path'] = $path;
			$link_state['previous_alias'] = $segment;

			if ($path === '') {
				/* We are done - can not walk deeper, link is made */
				return $query;
			}

			return $to_object->linkWalk($query, $link_state);
		}

		try {
			$to_object = $this->_findNextObject($object, $segment);
			if ($path === '') {
				$alias = $link_state['alias'] ?? $segment;
			} else {
				$alias = $segment;
			}
			$prev_alias = $link_state['previous_alias'] ?? $query->alias();
			if (!$query->findAlias($alias)) {
				$on = [
					'*' . $generator->columnAlias($to_object->idColumn(), $alias) => $generator->columnAlias($segment, $prev_alias),
				];
				$query->join_object($join_type, $to_object::class, $alias, $on);
			}
			if ($path === '') {
				return $query;
			}
			$link_state['path_walked'][] = $segment;
			$link_state['path'] = $path;
			$link_state['previous_alias'] = $alias;
			return $to_object->linkWalk($query, $link_state);
		} catch (Exception_ORMNotFound) {
		}

		try {
			$has_many = $this->hasMany($object, $segment);
			$to_object = $has_many['object'];
			$to_class = $has_many['class'];
			if ($path === '') {
				$alias = $link_state['alias'] ?? $segment;
			} else {
				$alias = $segment;
			}
			$prev_alias = $link_state['previous_alias'] ?? $query->alias();
			$mid_link = $alias . '_Link';
			if ($this->_hasManyQuery($object, $query, $has_many, $mid_link, $prev_alias, $join_type)) {
				// joining on intermediate table
				$on = ['*' . $generator->columnAlias($has_many['far_key'], $mid_link) => $generator->columnAlias($to_object->idColumn(), $alias), ];
			} else {
				// joining on intermediate table
				$on = ['*' . $generator->columnAlias($has_many['foreign_key'], $alias) => $generator->columnAlias($object->idColumn(), $prev_alias), ];
			}
			if (array_key_exists('on', $link_state) && is_array($add_on = $link_state['on'])) {
				foreach ($add_on as $k => $v) {
					$on["$alias.$k"] = $v;
				}
			}
			$query->join_object($join_type, $to_class, $alias, $on);
			if ($path === '') {
				return $query;
			}
			$link_state['path_walked'][] = $segment;
			$link_state['path'] = $path;
			$link_state['previous_alias'] = $segment;

			return $to_object->linkWalk($query, $link_state);
		} catch (Exception_Key) {
		}


		throw new Exception_Semantics("No path $segment found in " . $this->class . '::link_walk');
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @param ORMBase $this_object
	 * @param $query Database_Query_Select
	 * @param $many_spec array
	 * @param $alias string
	 *            Optional alias to use for the intermediate table
	 * @param null $link_alias
	 * @param bool $join_type
	 * @param $reverse boolean
	 *
	 * @return boolean
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Semantics
	 */

	/**
	 * @param ORMBase $this_object
	 * @param Database_Query_Select $query
	 * @param array $many_spec array
	 * @param string $alias alias
	 * @param string $link_alias string
	 * @param bool $join_type true=INNER false=LEFT OUTER
	 * @param bool $reverse If linking from far object to this
	 * @return bool true if intermediate table is used, false if not
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function _hasManyQuery(ORMBase $this_object, Database_Query_Select $query, array $many_spec, string
	&$alias = 'J', string $link_alias = '', bool|string $join_type = true, bool $reverse = false): bool {
		$result = false;
		$table = $many_spec['table'] ?? null;
		$foreign_key = $many_spec['foreign_key'];
		$query_class = $query->ormClass();
		$gen = $this->database()->sql();
		if (is_bool($join_type)) {
			$join_type = $join_type ? 'INNER' : 'LEFT OUTER';
		}
		if ($table !== null) {
			$result = true;
			// $class = $many_spec['class'];
			$object = $many_spec['object'];
			$far_key = $many_spec['far_key'];
			$alias = $alias . '_join';
			if ($link_alias === '') {
				$link_alias = $query->alias();
			}
			if ($reverse) {
				$id_column = $object->idColumn();
				$on = ['*' . $gen->columnAlias($far_key, $alias) => $gen->columnAlias($id_column, $link_alias), ];
			} else {
				$id_column = $this->id_column;
				$on = ['*' . $gen->columnAlias($foreign_key, $alias) => $gen->columnAlias($id_column, $link_alias), ];
			}

			$query->join_object($join_type, $object, $alias, $on, $table);
		}
		$logger = $this_object->application->logger;
		$this_alias = $alias;
		if ($this_object->hasPrimaryKeys()) {
			if (ORMBase::$debug) {
				$logger->debug($this_object::class . ' is NOT new');
			}
			$this_alias = $query_class === get_class($this) ? $query->alias() : $alias;
			$query->addWhere('*' . $gen->columnAlias($foreign_key, $this_alias), $this_object->id());
		} else {
			if (ORMBase::$debug) {
				$logger->notice($this_object::class . ' is  new');
			}
		}

		if (array_key_exists('order_by', $many_spec)) {
			$query->setOrderBy(ArrayTools::prefixValues(toList($many_spec['order_by']), "$this_alias."));
		}
		if (array_key_exists('where', $many_spec)) {
			$query->appendWhere($many_spec['where']);
		}
		return $result;
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @todo implement this
	 * @param ORMBase $this_object
	 * @param Database_Query_Update $query
	 * @param array $many_spec
	 * @param string $alias Optional alias to use for the intermediate table
	 * @param string $link_alias Optional alias to use for the intermediate table
	 * @param bool $join_type
	 * @param bool $reverse If linking from far object to this
	 * @return Database_Query_Update
	 * @throws Exception_Unimplemented
	 */
	final public function _hasManyQueryUpdate(
		ORMBase $this_object,
		Database_Query_Update $query,
		array $many_spec,
		string &$alias = 'J',
		string $link_alias = '',
		bool $join_type = true,
		bool $reverse =
		false
	): Database_Query_Update {
		throw new Exception_Unimplemented(__METHOD__, [
			'orm' => $this_object,
			'query' => $query,
			'many_spec' => $many_spec,
			'alias' => $alias,
			'link_alias' => $link_alias,
			'join_type' => $join_type,
			'reverse' => $reverse,
		]);
	}

	/**
	 *
	 * @param ORMBase $object
	 * @param array $many_spec
	 * @param string $alias
	 * @param bool $reverse
	 * @return Database_Query_Select
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function hasManyQueryDefault(ORMBase $object, array $many_spec, string $alias = 'J', bool $reverse = false): Database_Query_Select {
		$query = $many_spec['object']->querySelect($alias);
		$query->setFactory($object);
		$this->_hasManyQuery($object, $query, $many_spec, $alias, '', true, $reverse);
		return $query;
	}

	/**
	 *
	 * @param ORMBase $object
	 * @param array $many_spec
	 * @param string $alias
	 * @param bool $reverse
	 * @return Database_Query_Update
	 * @throws Exception_Unimplemented
	 */
	final public function hasManyQueryUpdateDefault(ORMBase $object, array $many_spec, string $alias = 'J', bool $reverse = false): Database_Query_Update {
		$query = $many_spec['object']->queryUpdate($alias);
		$this->_hasManyQueryUpdate($object, $query, $many_spec, $alias, '', true, $reverse);
		return $query;
	}

	/**
	 * @param ORMBase $object
	 * @param string $class
	 * @return array
	 */
	/**
	 * @param ORMBase $object
	 * @param string $class
	 * @return array
	 * @throws Exception_Key
	 * @throws Exception_Semantics|Exception_Configuration
	 */
	final public function hasManyObject(ORMBase $object, string $class): array {
		$class = $this->application->objects->resolve($class);
		$member = $this->has_many_objects[$class] ?? null;
		if (!$member) {
			throw new Exception_Key('No link from {object} to {class}', [
				'object' => $object::class, 'class' => $class,
			]);
		}
		return $this->hasMany($object, $member);
	}

	/**
	 * @param string $member
	 * @return array
	 * @throws Exception_Key
	 */
	final public function member(string $member): array {
		if (array_key_exists($member, $this->members)) {
			return $this->members[$member];
		}

		throw new Exception_Key('No such member {member} in class {class}', [
			'member' => $member, 'class' => get_class($this),
		]);
	}

	/**
	 * @param string $member
	 * @return mixed
	 * @throws Exception_Key
	 */
	final public function memberDefault(string $member): mixed {
		$memberStruct = $this->member($member);
		return $memberStruct['default'] ?? null;
	}

	/**
	 * Returns valid member names for this database table
	 *
	 * @return array
	 */
	final public function memberNames(): array {
		return array_keys($this->column_types + $this->has_one + $this->has_many);
	}

	final public function columnNames(): array {
		return array_keys($this->column_types + $this->has_one);
	}

	/**
	 * @param ORMBase $object
	 * @param string $member
	 * @return array
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	final public function hasMany(ORMBase $object, string $member): array {
		if (!array_key_exists($member, $this->has_many)) {
			throw new Exception_Key($member);
		}
		$has_many = $this->has_many[$member];
		if ($has_many[self::HAS_MANY_INITIALIZED] ?? false) {
			return $has_many;
		}
		$this->has_many[$member] = $this->hasManyInit($object, $has_many);
		return $this->has_many[$member];
	}

	/**
	 * Generate a query for a member
	 *
	 * @param ORMBase $this_object
	 * @param string $member
	 * @param ORMBase|null $object
	 *            Returned object class which represents the target object type
	 * @return Database_Query_Select
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function memberQuery(ORMBase $this_object, string $member, ORMBase &$object = null): Database_Query_Select {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics($this->class . "::memberQuery($member) called on non-many member");
		}
		$many_spec = $this->hasMany($this_object, $member);
		$query = $this->hasManyQueryDefault($this_object, $many_spec, $member, true);
		$object = $many_spec['object'];
		return $query;
	}

	/**
	 * Generate a query for a member
	 *
	 * @param ORMBase $this_object
	 * @param string $member
	 * @param ORMBase|null $object Returned object class which represents the target object type
	 * @return Database_Query_Update
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	final public function memberQueryUpdate(ORMBase $this_object, string $member, ORMBase &$object = null): Database_Query_Update {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Semantics($this->class . "::memberQuery($member) called on non-many member");
		}
		$many_spec = $this->hasMany($this_object, $member);
		$query = $this->hasManyQueryUpdateDefault($this_object, $many_spec, $member, true);
		$object = $many_spec['object'];
		return $query;
	}

	/**
	 * @param ORMBase $object
	 * @param string $member
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function memberForeignList(ORMBase $object, string $member): array {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Key(__CLASS__ . "::memberForeignList($member) called on non-many member");
		}
		$many_spec = $this->hasMany($object, $member);
		$query = $this->hasManyQueryDefault($object, $many_spec, $member, true);
		$far_key = $many_spec['far_key'];
		return $query->addWhat('X', $far_key)->toArray(null, 'X');
	}

	/**
	 * @param ORMBase $object
	 * @param string $member
	 * @param mixed $id
	 * @return bool
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Database_Exception_SQL
	 */
	final public function memberForeignExists(ORMBase $object, string $member, mixed $id): bool {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Key(__CLASS__ . "::memberForeignExists($member) called on non-many member");
		}
		$many_spec = $this->hasMany($object, $member);
		$query = $this->hasManyQueryDefault($object, $many_spec, $member, true);
		$far_key = $many_spec['far_key'];
		$what = 'COUNT(' . $this->database()->sql()->columnAlias($far_key) . ')';
		return $query->addWhat('*X', $what)->addWhere($far_key, $id)->integer('X') !== 0;
	}

	/**
	 * @param ORMBase $object
	 * @param string $member
	 * @return array[]
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	final public function memberForeignDelete(ORMBase $object, string $member): array {
		if (!isset($this->has_many[$member])) {
			throw new Exception_Key(__METHOD__ . "($member) called on non-many member");
		}
		$many_spec = $this->hasMany($object, $member);
		$table = $many_spec['table'] ?? null;
		$foreign_key = $many_spec['foreign_key'] ?? get_class($this);
		if ($table === null) {
			$table = $this->application->ormRegistry($many_spec['class'])->table();
		}
		return ['0-fk_delete-' . $table . '-' . $foreign_key => ['_fk_delete', $table, $foreign_key, ], ];
	}

	/**
	 * @param ORMBase $this_object
	 * @param string $member
	 * @param ORMBase $link
	 * @return array[]
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	final public function memberForeignAdd(ORMBase $this_object, string $member, ORMBase $link): array {
		$many_spec = $this->hasMany($this_object, $member);

		$class = $many_spec['class'];
		if (!$link instanceof $class) {
			throw new Exception_Key($link::class . " is not an instanceof $class");
		}
		$table = $many_spec['table'] ?? null;
		$foreign_key = $many_spec['foreign_key'];
		if ($table === null) {
			return ['1-fk_store' . $link . '-' . $foreign_key => ['_fk_store', $link, $foreign_key, ], ];
		}
		$far_key = $many_spec['far_key'];
		return [
			'1-fk_link_store' . $link . '-' . $foreign_key => [
				'_fk_link_store', $link, $table, [
					$far_key => '{Far}', $foreign_key => '{Foreign}',
				],
			],
		];
	}

	/**
	 * Ensure our has_many structure has all fields, add implied/default fields here.
	 *
	 * @param ORMBase $object
	 * @param array $has_many
	 * @return array
	 * @throws Exception_Semantics|Exception_Configuration|Exception_Key
	 * @todo Remove dependencies on "table" use "link_class" instead
	 */
	private function hasManyInit(ORMBase $object, array $has_many): array {
		$class = $has_many['class'];
		$my_class = $this->class;
		$link_class = $has_many['link_class'] ?? null;
		if ($link_class) {
			$this->application->classes->register($link_class);
			$table = $this->application->ormRegistry($link_class)->table();
			if (!$table) {
				throw new Exception_Configuration("$link_class::table", 'Link class for {class} {link_class} table is empty', [
					'class' => $object::class, 'link_class' => $link_class,
				]);
			}
			if (array_key_exists('table', $has_many)) {
				$this->application->logger->warning('Key "table" is ignored in has many definition: {table}', $has_many);
			}
			$has_many['table'] = $table;
		} else {
			$table = $has_many['table'] ?? null;
		}
		if ($this->inherit_options) {
			$object = $object->ormFactory($class, null, $object->inheritOptions());
		} else {
			$object = $this->application->ormRegistry($class);
		}
		if (!$object instanceof ORMBase) {
			throw new Exception_Semantics('{class} is not an instance of ORM', compact('class'));
		}
		if ($table === true) {
			// Clean up reference
			$has_many_object = $object->class_orm()->hasManyObject($object, $class);
			$table = $has_many_object['table'] ?? null;
			if (!is_string($table)) {
				throw new Exception_Semantics('{my_class} references table in {class}, but no table found for have_many', compact('my_class', 'class'));
			}
			$has_many['table'] = $table;
		}
		if (!array_key_exists('foreign_key', $has_many)) {
			$has_many['foreign_key'] = $my_class;
		}
		if (!array_key_exists('far_key', $has_many)) {
			$has_many['far_key'] = $table ? $class : $object->idColumn();
		}
		$has_many['object'] = $object;
		$has_many[self::HAS_MANY_INITIALIZED] = true;
		return $has_many;
	}

	/**
	 * Set the database for this object
	 *
	 * @param Database $set
	 * @return self
	 */
	final public function setDatabase(Database $set): self {
		$this->database = $set;
		$this->database_name = $set->codeName();
		$this->application->ormModule()->clearNamedCache($this->class);
		return $this;
	}

	/**
	 * Retrieve the database for this object
	 *
	 * @return Database
	 */
	final public function database(): Database {
		if ($this->database instanceof Database) {
			return $this->database;
		}
		return $this->database = $this->application->database_registry($this->database_name);
	}

	/**
	 * Retrieve the table for this object
	 *
	 * @return string
	 */
	public function table(): string {
		return $this->table;
	}

	/**
	 * Retrieve the table for this object
	 *
	 * @param string $set
	 * @return self
	 */
	public function setTable(string $set): self {
		$this->table = $set;
		$this->application->ormModule()->clearNamedCache($this->class);
		return $this;
	}

	/**
	 * Get the schema for this object
	 *
	 * @param ORMBase|null $object
	 * @param string $sql
	 * @return Schema
	 * @throws Exception_ORMNotFound
	 */
	private function _database_schema(ORMBase $object = null, string $sql = ''): Schema {
		[$namespace, $class] = PHP::parseNamespaceClass($this->class);

		try {
			if ($namespace) {
				$namespace .= '\\';
			}
			$ormSchema = $this->application->objects->factory($namespace . 'Schema_' . $class, $this, $object);
			assert($ormSchema instanceof Schema);
			return $ormSchema;
		} catch (Exception_Class_NotFound $e) {
			$schema = new Schema_File($this, $object, $sql);
			if ($schema->exists() || $schema->has_sql()) {
				return $schema;
			}

			throw new Exception_ORMNotFound($this->class, 'Can not find schema for {class} in {searches}, or schema object {exception}', [
				'class' => $this->class, 'searches' => "\n" . implode("\n\t", $schema->searches()) . "\n",
				'exception' => $e,
			], $e);
		} catch (Exception $e) {
			$this->application->hooks->call('exception', $e);

			throw new Exception_ORMNotFound($this->class, 'Schema error for {class} Exception: {previousClass}', [
				'previousClass' => $e::class,
			] + Exception::exceptionVariables($e), $e);
		}
	}

	/**
	 *
	 * @param ORMBase $object
	 * @return Schema
	 * @throws Exception_ORMNotFound
	 */
	final public function database_schema(ORMBase $object): Schema {
		$result = $object->schema();
		if ($result === null || $result instanceof Schema) {
			return $result;
		}
		if (is_array($result)) {
			return $this->_database_schema($object, implode(";\n", $result));
		}
		assert(is_string($result));
		return $this->_database_schema($object, $result);
	}

	/**
	 * Override this in subclasses to provide an alternate schema
	 *
	 * @param ORMBase $object
	 * @return string|array|Schema
	 * @throws Exception_ORMNotFound
	 */
	public function schema(ORMBase $object): string|array|Schema {
		return $this->_database_schema($object);
	}

	/**
	 * Set up ->column_defaults
	 */
	private function _columnDefaults(): void {
		$column_types = $this->column_types;
		foreach (array_keys($this->column_types) as $column) {
			if (array_key_exists($column, $this->column_defaults)) {
				continue;
			}
			$this->_memberDefault($column, $column_types[$column] ?? null, $this->column_defaults);
		}
	}

	/**
	 * Take a database result and convert it into the internal data array
	 *
	 * @param ORMBase $object
	 * @param array $data
	 * @return array
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	final public function from_database(ORMBase $object, array $data): array {
		$column_types = $this->column_types;
		$data = $object->sql()->from_database($object, $data);
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->memberFromDatabase($object, $column, $column_types[$column], $data);
		}
		return $data;
	}

	/**
	 * Take an external array and convert it into the internal data array
	 *
	 * @param ORMBase $object
	 * @param array $data
	 * @return array
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	final public function from_array(ORMBase $object, array $data): array {
		$column_types = $this->column_types;
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->memberFromArray($object, $column, $column_types[$column], $data);
		}
		return $data;
	}

	/**
	 * Take internal data and convert into a form consumable by database calls
	 *
	 * @param ORMBase $object
	 * @param array $data
	 * @param boolean $insert
	 *            This is an insert (vs update)
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function to_database(ORMBase $object, array $data, bool $insert = false): array {
		$data = $object->sql()->to_database($object, $data, $insert);
		$column_types = $this->column_types;
		$columns = array_keys(count($data) < count($column_types) ? $data : $column_types);
		foreach ($columns as $column) {
			if (!array_key_exists($column, $column_types) || !array_key_exists($column, $data)) {
				continue;
			}
			$this->memberToDatabase($object, $column, $column_types[$column], $data, $insert);
		}
		return $data;
	}

	/**
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @return void
	 */
	private function _memberDefault(string $column, string $type, array &$data): void {
		$data[$column] = match ($type) {
			self::type_polymorph => '',
			self::type_created, self::TYPE_MODIFIED => 'now',
			default => null,
		};
	}

	/**
	 * Generate the desired zesk ORM class name to instantiate this object
	 *
	 * Override in subclasses to get different polymorphic behavior.
	 *
	 * @param string $value
	 * @return string
	 */
	protected function polymorphicClassGenerate(string $value): string {
		return $this->polymorphic . '_' . ucfirst($value);
	}

	/**
	 * Convert the existing ORM class name to the preferred code used in the database
	 *
	 * Override in subclasses to get different polymorphic behavior.
	 *
	 * @param ORMBase $object
	 * @param string $column
	 *            Column which is generating this value
	 * @return string
	 */
	protected function polymorphicClassParse(ORMBase $object, string $column): string {
		$class = StringTools::removePrefix($object::class, [$this->polymorphic . '_', $object::class, ], true);
		return strtolower($class);
	}

	/**
	 * Convert member from database to internal format.
	 * Result value is hints to calling function about additional properties to set in the object.
	 *
	 * Currently, passes back polymorphic_leaf class.
	 *
	 * @param ORMBase $object
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @return void
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	private function memberFromDatabase(ORMBase $object, string $column, string $type, array &$data): void {
		$v = $data[$column];
		switch ($type) {
			case self::type_real:
			case self::type_float:
			case self::TYPE_FLOAT:
			case self::type_decimal:
				if ($v === null) {
					break;
				}
				$data[$column] = toFloat($v);

				break;
			case self::type_text:
			case self::TYPE_STRING:
				if ($v === null) {
					break;
				}
				$data[$column] = strval($v);

				break;
			case self::type_hex:
			case self::type_hex32:
				$data[$column] = Hexadecimal::encode($v);

				break;
			case self::TYPE_OBJECT:
				if (empty($v)) {
					$data[$column] = null;
				} else {
					$data[$column] = $v;
				}

				break;
			case self::TYPE_ID:
			case self::TYPE_INTEGER:
				$data[$column] = toInteger($v);

				break;
			case self::type_boolean:
				$data[$column] = toBool($v);

				break;
			case self::TYPE_SERIALIZE:
				$data[$column] = $result = empty($v) ? null : @unserialize($v);
				if ($result === false && $v !== 'b:0;') {
					$this->application->logger->error('unserialize of {n} bytes failed: {data}', [
						'n' => strlen($v), 'data' => substr($v, 0, 100),
					]);
				}

				break;
			case self::type_json:
				try {
					$data[$column] = empty($v) ? null : JSON::decode($v);
				} catch (Exception_Parse $e) {
					$this->application->logger->error('Unable to parse JSON in {class}->{column} {json}', [
						'class' => $object::class, 'column' => $column, 'json' => $v,
					]);

					throw $e;
				}

				break;
			case self::type_created:
			case self::TYPE_MODIFIED:
			case self::TYPE_TIMESTAMP:
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
			case self::TYPE_IP4:
				$data[$column] = $v === null ? null : IPv4::from_integer($v);

				break;
			case self::type_polymorph:
				if ($v) {
					if (empty($this->polymorphic)) {
						$this->application->logger->error('{class} has polymorph member {column} but is not polymorphic', [
							'class' => get_class($this), 'column' => $column,
						]);

						break;
					}
					$full_class = $this->polymorphicClassGenerate($v);
					// 					$this->application->logger->debug("Setting object {class} polymorphic to {full_class} (polymorphic={polymorphic}, v={v})", array(
					// 						"class" => get_class($object),
					// 						"polymorphic" => $this->polymorphic,
					// 						"v" => $v,
					// 						"full_class" => $full_class
					// 					));
					$object->setPolymorphicLeaf($full_class);
				}

				break;
			default:
				throw new Exception_Semantics("Invalid column type $type");
		}
	}

	/**
	 * Convert member into to internal format.
	 * Result value is hints to calling function about additional properties to set in the object.
	 *
	 * May set polymorphic_leaf class.
	 *
	 * @param ORMBase $object
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @return void
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	private function memberFromArray(ORMBase $object, string $column, string $type, array &$data): void {
		switch ($type) {
			case self::type_hex:
			case self::type_hex32:
			case self::TYPE_SERIALIZE:
			case self::type_json:
				break;
			case self::type_ip:
			case self::TYPE_IP4:
				if (is_int($data[$column])) {
					$data[$column] = IPv4::from_integer($data[$column]);
				}

				break;
			default:
				$this->memberFromDatabase($object, $column, $type, $data);

				break;
		}
	}

	/**
	 * Return the SQL version for now
	 *
	 * @param ORMBase $object
	 * @return string
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_ORMNotFound
	 */
	private function sqlNow(ORMBase $object): string {
		$generator = $object->database()->sql();
		return $this->utc_timestamps ? $generator->nowUTC() : $generator->now();
	}

	/**
	 * Convert a member into format suitable for the database
	 *
	 * @param ORMBase $object
	 * @param string $column
	 * @param string $type
	 * @param array $data
	 * @param bool $insert If this is an insertion
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	final public function memberToDatabase(ORMBase $object, string $column, string $type, array &$data, bool $insert = false): void {
		if (!array_key_exists($column, $data)) {
			throw new Exception_Semantics('Can not call {orm}->member_to_database_twice on same column {column} {type} keys: {keys}', [
				'orm' => $object::class, 'column' => $column, 'type' => $type, 'keys' => array_keys($data),
			]);
		}
		$v = $data[$column];
		switch ($type) {
			case self::type_polymorph:
				$data[$column] = $this->polymorphicClassParse($object, $column);

				break;
			case self::type_real:
			case self::type_float:
			case self::TYPE_FLOAT:
			case self::type_decimal:
				$data[$column] = $v === null || $v === '' ? null : floatval($v);

				break;
			case self::type_text:
			case self::TYPE_STRING:
				$data[$column] = $v === null ? null : strval($v);

				break;
			case self::TYPE_OBJECT:
				$data[$column] = $v === null ? null : ORMBase::mixedToID($v);

				break;
			case self::type_crc32:
				if (isset($this->crc_column)) {
					$data["*$column"] = 'CRC32(' . $this->database()->quoteName($object->member($this->crc_column)) . ')';
				}
				unset($data[$column]);

				break;
			case self::type_hex:
			case self::type_hex32:
				$data[$column] = Hexadecimal::decode($v);

				break;
			case self::TYPE_ID:
			case self::TYPE_INTEGER:
				$data[$column] = $v === null ? null : toInteger($v, intval($v));

				break;
			case self::type_byte:
				$data[$column] = toInteger($v, $v) % 255;

				break;
			case self::type_boolean:
				$data[$column] = toBool($v) ? 1 : 0;

				break;
			case self::TYPE_SERIALIZE:
				$data[$column] = serialize($v);

				break;
			case self::type_json:
				$data[$column] = JSON::encode($v);

				break;
			case self::type_created:
				unset($data[$column]);
				if ($insert) {
					$data["*$column"] = $this->sqlNow($object);
				}

				break;
			case self::TYPE_MODIFIED:
				unset($data[$column]);
				$data["*$column"] = $this->sqlNow($object);

				break;
			case self::TYPE_TIMESTAMP:
			case self::type_datetime:
				if (empty($v)) {
					$data[$column] = null;
				} elseif ($v === 'now') {
					unset($data[$column]);
					$data["*$column"] = $this->sqlNow($object);
				} elseif ($v instanceof Timestamp) {
					$data[$column] = $v->sql();
				} elseif (is_numeric($v)) {
					$data[$column] = gmdate('Y-m-d H:i:s', $v);
				}

				break;
			case self::type_date:
				if (empty($v)) {
					$data[$column] = null;
				} elseif ($v === 'now') {
					unset($data[$column]);
					$data["*$column"] = $this->sqlNow($object);
				} elseif ($v instanceof Temporal) {
					$data[$column] = $v->format(null, '{YYYY}-{MM}-{DD}');
				} elseif (is_numeric($v)) {
					$data[$column] = gmdate('Y-m-d', $v);
				}

				break;
			case self::type_time:
				if (empty($v)) {
					$data[$column] = null;
				} elseif ($v === 'now') {
					unset($data[$column]);
					$data["*$column"] = $this->sqlNow($object);
				} elseif ($v instanceof Temporal) {
					$data[$column] = $v->format(null, '{hh}:{mm}:{ss}');
				} elseif (is_numeric($v)) {
					$data[$column] = gmdate('H:i:s', $v);
				}

				break;
			case self::TYPE_IP4:
			case self::type_ip:
				if ($v === null) {
					$data[$column] = 'NULL';
					return;
				}

				try {
					$gen = $this->database()->sql();
					$data["*$column"] = $gen->function_ip2long($gen->quoteText($v));
					unset($data[$column]);
				} catch (Exception_Class_NotFound) {
					// TODO: Should we do something here?
				}
				break;
			default:
				throw new Exception_Semantics("Invalid column type $type");
		}
	}

	/**
	 * Guess column types
	 *
	 * Updates internal $this->column_types
	 * @throws Exception_Class_NotFound
	 */
	private function implyColumnTypes(): void {
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
	public function schemaMap(): array {
		// Some of these are for MySQL only. Good/bad? TODO
		return $this->optionArray('schema_map') + [
			'name' => $this->name, 'code_name' => $this->code_name, 'table' => $this->table, 'extra_keys' => '',
			'auto_increment' => $this->auto_column ? 'AUTO_INCREMENT' : '',
			'primary_keys' => implode(',', $this->primary_keys),
		];
	}

	/**
	 * Does this object have the following column?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasColumn(string $name): bool {
		return array_key_exists($name, $this->column_types);
	}

	/**
	 * Class variables
	 */
	public function variables(): array {
		return [
			'name' => $class_name = $this->application->locale->__($this->name),
			'names' => $this->application->locale->plural($class_name), 'name_column' => $this->name_column,
			'id_column' => $this->id_column, 'primary_keys' => $this->primary_keys, 'class' => get_class($this),
		];
	}

	/**
	 * Retrieve a list of class dependencies for this object
	 */
	public function dependencies(ORMBase $object): array {
		$result = [];
		foreach ($this->has_one as $class) {
			if ($class[0] !== '*') {
				$result['requires'][] = $class;
			}
		}
		foreach (array_keys($this->has_many) as $member) {
			try {
				$has_many = $this->hasMany($object, $member);
				$result['requires'][] = $has_many['class'];
				$link_class = $has_many['link_class'] ?? null;
				if ($link_class) {
					$result['requires'][] = $link_class;
				}
			} catch (Exception_Configuration|Exception_Key|Exception_Semantics) {
				// pass
			}
		}

		return $result;
	}
}
