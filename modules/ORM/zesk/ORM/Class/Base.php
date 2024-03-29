<?php
declare(strict_types=1);
/**
 * Class abstraction for ORM Relational Map
 *
 * This is where the magic happens for ORMs
 *
 * Copyright &copy; 2023, Market Acumen, Inc.
 * @author kent
 * @see ORMBase
 */

namespace zesk\ORM;

use Psr\Cache\InvalidArgumentException;
use Throwable;
use zesk\Application;
use zesk\Application\Hooks;
use zesk\ArrayTools;
use zesk\Database\Base;
use zesk\Database\Column;
use zesk\Database\Exception\Connect;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Database\Index;
use zesk\Database\SQLParser;
use zesk\Date;
use zesk\Exception as BaseException;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Exception\Unimplemented;
use zesk\File;
use zesk\Hexadecimal;
use zesk\Hookable;
use zesk\IPv4;
use zesk\JSON;
use zesk\Kernel;
use zesk\ORM\Database\Query\Select;
use zesk\ORM\Database\Query\Update;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\ORM\Schema as Schema;
use zesk\PHP;
use zesk\StringTools;
use zesk\Temporal;
use zesk\Time;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 * @see ORMBase
 */
class Class_Base extends Hookable {
	/**
	 * Class name
	 */
	public const OPTION_NAME = 'name';

	/**
	 * Table name
	 */
	public const OPTION_TABLE = 'table';

	/**
	 * Table prefix used when table is computed.
	 */
	public const OPTION_TABLE_PREFIX = 'table_prefix';

	/**
	 * ID column for this table
	 */
	public const OPTION_ID_COLUMN = 'id_column';

	/**
	 * Default ID column when nothing specified
	 */
	public const DEFAULT_OPTION_ID_COLUMN = 'id';

	public const HAS_MANY_INITIALIZED = '*init*';

	/**
	 *
	 */
	public const ID_AUTOMATIC = '*';

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
	public const TYPE_TEXT = 'text';

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
	public const TYPE_POLYMORPH = 'polymorph';

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const TYPE_OBJECT = self::TYPE_ORM;

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const TYPE_ORM = 'orm';

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	public const TYPE_CREATED = 'created';

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	public const TYPE_MODIFIED = 'modified';

	/**
	 * String information called using serialize
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
	public const TYPE_JSON = 'json';

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
	public const TYPE_CHARACTER = 'character';

	/**
	 * Single-precision floating point number
	 *
	 * @var string
	 */
	public const TYPE_REAL = 'real';

	/**
	 *
	 * @var string
	 */
	public const TYPE_FLOAT = 'float';

	/**
	 *
	 * @var string
	 */
	public const TYPE_DOUBLE = 'double';

	/**
	 *
	 * @var string
	 */
	public const TYPE_DECIMAL = 'decimal';

	/**
	 *
	 * @var string
	 */
	public const TYPE_BYTE = 'byte';

	/**
	 *
	 * @var string
	 */
	public const TYPE_BINARY = 'binary';

	/**
	 *
	 * @var string
	 */
	public const TYPE_BOOL = 'boolean';

	/**
	 *
	 * @var string
	 */
	public const TYPE_TIMESTAMP = 'timestamp';

	/**
	 *
	 * @var string
	 */
	public const TYPE_DATETIME = 'datetime';

	/**
	 *
	 * @var string
	 */
	public const TYPE_DATE = 'date';

	/**
	 *
	 * @var string
	 */
	public const TYPE_TIME = 'time';

	/**
	 *
	 * @var string
	 */
	public const TYPE_IP = 'ip';

	/**
	 *
	 * @var string
	 */
	public const TYPE_IP4 = 'ip4';

	/**
	 *
	 * @var string
	 */
	public const TYPE_CRC32 = 'crc32';

	/**
	 *
	 * @var string
	 */
	public const TYPE_HEX32 = 'hex';

	/**
	 *
	 * @var string
	 */
	public const TYPE_HEX = 'hex';

	/**
	 * PHP Class which created this (type ORM)
	 *
	 * @var string
	 */
	public string $class;

	/**
	 * String name of the database to use
	 * <code>
	 * public string $database_name = "tracker";
	 * </code>
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
	 * Database where this object can be fetched and stored.
	 * If not specified, the default database.
	 *
	 * @var Base
	 */
	private Base $database;

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
	 * - "hex" does hex encoding and decoding
	 * - "integer" converts to integer
	 * - "boolean" converts to boolean from integer
	 * - "serialize" serializes PHP objects
	 * - "crc" is a CRC checksum on another column specified by ->checksum_column
	 *
	 * @var array
	 */
	public array $column_types = [];

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
	public string $id_column = self::ID_AUTOMATIC;

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
	 * Whether this object has its columns determined programmatically based on what the database schema is.
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
		[$namespace, $suffix] = StringTools::reversePair($classname, '\\', '', $classname, 'left');
		return $namespace . 'Class_' . $suffix;
	}

	/**
	 * Create a new class instance - should only be called from ORM
	 *
	 * @param ORMBase $object
	 * @param array $options
	 * @param string|null $class
	 * @return Class_Base
	 * @throws ClassNotFound
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
		$instance = self::$classes[$lowClass] = $application->objects->factoryArguments($class_class, [
			$object, $options,
		]);
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
	 * @return void
	 * @throws Semantics
	 */
	public function __wakeup(): void {
		$this->application = Kernel::wakeupApplication();
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
	 * @throws KeyNotFound
	 */
	public static function linkMany(string $class, string $member, array $many_spec): void {
		if (!array_key_exists('class', $many_spec)) {
			throw new KeyNotFound('many_spec for class {class} must contain key \'class\' for member {member}', compact('class', 'member'));
		}
		$lowClass = strtolower($class);
		if (array_key_exists($lowClass, self::$classes)) {
			$class = self::$classes[$lowClass];
			$class->_addMany($member, $many_spec);
		} else {
			if (isset(self::$defer_class_links[$lowClass][$member])) {
				throw new KeyNotFound('Double link_many added for {class} {member}', compact('class', 'member'));
			}
			self::$defer_class_links[$lowClass][$member] = $many_spec;
		}
	}

	/**
	 * When registering the object, add deferred
	 *
	 * @param string $class
	 * @return void
	 * @throws KeyNotFound
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
	private function _addHasManyObject(string $class, string $member, bool $first = false): void {
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
	 * @throws KeyNotFound
	 */
	protected function _addMany(string $member, array $many_spec): self {
		if (!array_key_exists('class', $many_spec)) {
			throw new KeyNotFound('many_spec for class {class} must contain key \'class\' for member {member}', [
				'class' => get_class($this), 'member' => $member,
			]);
		}
		$this->_addHasManyObject($many_spec['class'], $member, Types::toBool($many_spec['default'] ?? false));
		$this->has_many[$member] = ArrayTools::map($many_spec, ['table' => $this->table, ]);
		return $this;
	}

	/**
	 * Constructor
	 * @param ORMBase $object
	 * @param array $options
	 * @throws Semantics
	 * @throws NotFoundException
	 * @throws KeyNotFound
	 */
	public function __construct(ORMBase $object, array $options = []) {
		$app = $object->application;
		parent::__construct($app, $options);
		$this->inheritConfiguration();
		// Handle polymorphic classes - create correct Class and use correct base class
		$this->class = $object->class_orm_name();

		/* May change $this->>class */
		$this->configure($object);

		$this->application->classes->register($this->class);
		$this->application->hooks->registerClass($this->class);

		$this->initializeDatabase($object);
		$this->configureColumns($object);
		$this->_deriveClassConfiguration($object);
		$this->_initializeColumnTypes();
		$this->_columnDefaults();
		if (count($this->members) === 0) {
			$this->_deriveMembers();
		}
		$this->initialize();
	}

	/**
	 * If blank, sets:
	 *
	 * $this->>codeName
	 * $this->>name
	 * $this->>table
	 *
	 * Sets:
	 *
	 * $this->>id_column
	 *
	 * @param ORMBase $object
	 * @return void
	 * @throws Semantics
	 * @throws KeyNotFound
	 */
	private function _deriveClassConfiguration(ORMBase $object): void {
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
		$this->_derivePrimaryKeysAndID();
		$this->_deriveFindAndDuplicates();
		$this->_deriveLinkOne();
		$this->_deriveLinkMany();
	}

	private function _derivePrimaryKeysAndID(): void {
		/* Automatic promotion here of primary_keys should be avoided - id_column should probably just be internal */
		if (count($this->primary_keys) > 0) {
			if (count($this->primary_keys) === 1) {
				$this->id_column = $this->primary_keys[0];
			} elseif ($this->id_column === self::ID_AUTOMATIC) {
				$this->id_column = '';
			}
		} elseif ($this->id_column === self::ID_AUTOMATIC) {
			$this->id_column = $this->optionString(self::OPTION_ID_COLUMN, self::DEFAULT_OPTION_ID_COLUMN);
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
	}

	private function _deriveFindAndDuplicates(): void {
		if (empty($this->find_keys)) {
			$this->find_keys = $this->primary_keys;
		}
		if (empty($this->duplicate_keys)) {
			$this->duplicate_keys = [];
		}
	}

	/**
	 * @throws Semantics
	 * @throws KeyNotFound
	 */
	private function _deriveLinkMany(): void {
		$this->_addDeferLinkMany($this->class);
		if (count($this->has_many) !== 0) {
			foreach ($this->has_many as $member => $many_spec) {
				if (!is_array($many_spec)) {
					throw new Semantics('many_spec for class {class} must have array value for member {member}', [
						'class' => $this->class, 'member' => $member,
					]);
				}
				if (!array_key_exists('class', $many_spec)) {
					throw new Semantics('many_spec for class {class} must contain key \'class\' for member {member}', [
						'class' => $this->class, 'member' => $member,
					]);
				}
				$this->_addHasManyObject($many_spec['class'], $member, Types::toBool($many_spec['default'] ?? false));
			}
			$this->has_many = ArrayTools::map($this->has_many, ['table' => $this->table, ]);
		}
	}

	private function _deriveLinkOne(): void {
		if (count($this->has_one) !== 0) {
			$this->has_one_flip = [];
			foreach ($this->has_one as $member => $class) {
				if ($class[0] !== '*') {
					$this->has_one[$member] = $class = $this->application->objects->resolve($class);
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
	}

	/**
	 * @return void
	 * @throws Semantics
	 */
	public function schemaChanged(): void {
		if ($this->dynamic_columns) {
			$this->_initializeColumnTypes();
		}
	}

	/**
	 * Set up the ->members value as it should be populated for forwards compatibility.
	 *
	 * Ultimately this will be member -> self::memberID(...)
	 *
	 * @return void
	 */
	private function _deriveMembers(): void {
		foreach ($this->column_types as $column_name => $column_type) {
			$default = $this->column_defaults[$column_name] ?? null;
			$this->members[$column_name] = [
				'type' => $column_type, 'default' => $default,
			] + (($default === null) ? ['null' => true] : []) + ($this->members[$column_name] ?? []);
			unset($this->members[$column_name]['idPrimaryKey']);
		}
		foreach ($this->primary_keys as $primary_key) {
			$this->members[$primary_key]['primaryKey'] = true;
		}
		if ($this->id_column) {
			$this->members[$this->id_column]['id'] = true;
			$this->members[$this->id_column]['idPrimaryKey'] = true;
		}
		if ($this->auto_column) {
			$this->members[$this->auto_column]['increment'] = true;
		}
		foreach ($this->has_one as $column_name => $className) {
			$this->members[$column_name]['class'] = $className;
		}
		foreach ($this->has_many as $column_name => $manyStruct) {
			$this->members[$column_name] = ['type' => 'many'] + $manyStruct;
		}
		foreach ($this->find_keys as $find_key) {
			$this->members[$find_key]['findKey'] = true;
		}
		foreach ($this->duplicate_keys as $duplicate_key) {
			$this->members[$duplicate_key]['duplicateKey'] = true;
		}
	}

	/**
	 * Sets $this->>database_name based on $this->>database_group (if set)
	 * Sets $this->>database to a valid value (not connected)
	 *
	 * @param ORMBase $object
	 * @return void
	 * @throws NotFoundException
	 */
	protected function initializeDatabase(ORMBase $object): void {
		if (!empty($this->database_group) && $this->database_group !== $this->class) {
			if ($this->database_name !== '') {
				$this->application->logger->warning('database_name value {database_name} is ignored, using database_group {database_group}', [
					'database_name' => $this->database_name, 'database_group' => $this->database_group,
				]);
			}
			$this->database_name = $this->application->ormRegistry($this->database_group)->databaseName();
		}
		if ($this->database_name === $object->databaseName() && !$object->initializing()) {
			$this->database = $object->database();
		} else {
			try {
				$this->database = $this->application->databaseModule()->databaseRegistry($this->database_name, [
					'connect' => false,
				]);
			} catch (Connect $e) {
				throw new NotFoundException('databaseRegistry({name}) connect error when connect is false', [
					'name' => $this->database_name,
				], 0, $e);
			}
		}
	}

	/**
	 * Set up any columns which need to be set up automatically.
	 *
	 * @param ORMBase $object
	 * @return void
	 */
	protected function configureColumns(ORMBase $object): void {
		// pass
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
	 * @return void
	 * @throws Semantics
	 */
	final public function _initializeColumnTypes(): void {
		if (count($this->column_types) === 0) {
			$this->dynamic_columns = true;
		} elseif (!$this->dynamic_columns) {
			return;
		}
		/* Loaded already or initialized already */
		if (count($this->table_columns) !== 0) {
			return;
		}
		/* Loaded already or initialized already */
		if (count($this->column_types) > 0 && count($this->primary_keys) === 0) {
			throw new Semantics('No support for database synchronized column without primary_keys defined {class}', ['class' => get_class($this)]);
		}
		$this->_loadColumns();
		if (count($this->column_types) === 0 && count($this->table_columns) > 0) {
			$this->_implyColumnTypes();
		}
	}

	/**
	 * Load database columns from database/cache
	 *
	 * @return void
	 * @throws Semantics
	 */
	private function _loadColumns(): void {
		$pool = $this->application->cacheItemPool();
		$table = $this->table;

		try {
			$cacheItem = $pool->getItem(__CLASS__ . "::column_cache::$table");
			if ($cacheItem->isHit()) {
				$this->table_columns = $cacheItem->get();
				return;
			}
		} catch (InvalidArgumentException) {
			$cacheItem = null;
		}

		try {
			$columns = $this->database()->tableColumns($this->table);
			$this->table_columns = [];
			foreach ($columns as $object) {
				$name = $object->name();
				$this->table_columns[$name] = $object->sql_type();
			}
			if ($cacheItem) {
				$pool->saveDeferred($cacheItem->set($this->table_columns));
			}
		} catch (BaseException $e) {
			if ($cacheItem) {
				try {
					$pool->deleteItem($cacheItem->getKey());
				} catch (InvalidArgumentException) {
				}
			}

			throw new Semantics('No database table columns for {table}', ['table' => $table], 0, $e);
		}
	}

	/**
	 * Given a class $class, determine the default path to the class
	 *
	 * @param $class string
	 * @return string
	 * @throws ORMNotFound
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
			throw new ORMNotFound($class, 'No has many');
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
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	private function _findNextObject(ORMBase $object, string $segment): ORMBase {
		if (array_key_exists($segment, $this->has_one)) {
			$to_class = $this->has_one[$segment];
			if ($to_class[0] === '*') {
				$to_class = $object->member(substr($to_class, 1));
			}
			return $this->application->ormRegistry($to_class);
		}

		throw new ORMNotFound($segment, 'Next segment has no object');
	}

	/**
	 * @param ORMBase $object
	 * @param Select $query
	 * @param array $link_state
	 * @return Select
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 */
	final public function linkWalk(ORMBase $object, Select $query, array $link_state = []): Select {
		$generator = $this->database()->sqlDialect();
		$path = $link_state['path'] ?? '';
		if ($path === '') {
			throw new Semantics($this->class . '::link_walk: No path in ' . serialize($link_state));
		}
		[$segment, $path] = StringTools::pair($path, '.', $path);
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
		} catch (ORMNotFound) {
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
		} catch (KeyNotFound) {
		}


		throw new Semantics("No path $segment found in " . $this->class . '::link_walk');
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @param ORMBase $this_object
	 * @param $query Select
	 * @param $many_spec array
	 * @param $alias string
	 *            Optional alias to use for the intermediate table
	 * @param null $link_alias
	 * @param bool $join_type
	 * @param $reverse boolean
	 *
	 * @return boolean
	 * @throws ClassNotFound
	 * @throws Semantics
	 */

	/**
	 * @param ORMBase $this_object
	 * @param Select $query
	 * @param array $many_spec array
	 * @param string $alias alias
	 * @param string $link_alias string
	 * @param bool $join_type true=INNER false=LEFT OUTER
	 * @param bool $reverse If linking from far object to this
	 * @return bool true if intermediate table is used, false if not
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 */
	final public function _hasManyQuery(ORMBase $this_object, Select $query, array $many_spec, string &$alias = 'J', string $link_alias = '', bool|string $join_type = true, bool $reverse = false): bool {
		try {
			$id = $this_object->id();
		} catch (Throwable $t) {
			throw new ORMEmpty($this_object::class, 'ID fetch resulted in {class} {message}', BaseException::exceptionVariables($t), $t);
		}
		$result = false;
		$table = $many_spec['table'] ?? null;
		$foreign_key = $many_spec['foreign_key'];
		$query_class = $query->ormClass();
		$gen = $this->database()->sqlDialect();
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
			$query->addWhere('*' . $gen->columnAlias($foreign_key, $this_alias), $id);
		} else {
			if (ORMBase::$debug) {
				$logger->notice($this_object::class . ' is  new');
			}
		}

		if (array_key_exists('order_by', $many_spec)) {
			$query->setOrderBy(ArrayTools::prefixValues(Types::toList($many_spec['order_by']), "$this_alias."));
		}
		if (array_key_exists('where', $many_spec)) {
			$query->appendWhere($many_spec['where']);
		}
		return $result;
	}

	/**
	 * Adds an intermediate join clause to a query for the has_many specified
	 *
	 * @param ORMBase $this_object
	 * @param Update $query
	 * @param array $many_spec
	 * @param string $alias Optional alias to use for the intermediate table
	 * @param string $link_alias Optional alias to use for the intermediate table
	 * @param bool $join_type
	 * @param bool $reverse If linking from far object to this
	 * @return Update
	 * @throws Unimplemented
	 * @todo implement this
	 */
	final public function _hasManyQueryUpdate(ORMBase $this_object, Update $query, array $many_spec, string $alias = 'J', string $link_alias = '', bool $join_type = true, bool $reverse = false): Update {
		if ($query->table()) {
			/* How did I get here */
			throw new Unimplemented(__METHOD__, [
				'orm' => $this_object, 'query' => $query, 'many_spec' => $many_spec, 'alias' => $alias,
				'link_alias' => $link_alias, 'join_type' => $join_type, 'reverse' => $reverse,
			]);
		}
		return $query;
	}

	/**
	 *
	 * @param ORMBase $object
	 * @param array $many_spec
	 * @param string $alias
	 * @param bool $reverse
	 * @return Select
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 */
	final public function hasManyQueryDefault(ORMBase $object, array $many_spec, string $alias = 'J', bool $reverse = false): Select {
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
	 * @return Update
	 * @throws Unimplemented
	 */
	final public function hasManyQueryUpdateDefault(ORMBase $object, array $many_spec, string $alias = 'J', bool $reverse = false): Update {
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
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 */
	final public function hasManyObject(ORMBase $object, string $class): array {
		$class = $this->application->objects->resolve($class);
		$member = $this->has_many_objects[$class] ?? null;
		if (!$member) {
			throw new KeyNotFound('No link from {object} to {class}', [
				'object' => $object::class, 'class' => $class,
			]);
		}
		return $this->hasMany($object, $member);
	}

	/**
	 * @param string $member
	 * @return array
	 * @throws KeyNotFound
	 */
	final public function member(string $member): array {
		if (array_key_exists($member, $this->members)) {
			return $this->members[$member];
		}

		throw new KeyNotFound('No such member {member} in class {class}', [
			'member' => $member, 'class' => get_class($this),
		]);
	}

	/**
	 * @param string $member
	 * @return mixed
	 * @throws KeyNotFound
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
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 */
	final public function hasMany(ORMBase $object, string $member): array {
		if (!array_key_exists($member, $this->has_many)) {
			throw new KeyNotFound($member);
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
	 * @return Select
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 */
	final public function memberQuery(ORMBase $this_object, string $member, ORMBase &$object = null): Select {
		if (!isset($this->has_many[$member])) {
			throw new Semantics($this->class . "::memberQuery($member) called on non-many member");
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
	 * @return Update
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws Semantics
	 * @throws Unimplemented
	 */
	final public function memberQueryUpdate(ORMBase $this_object, string $member, ORMBase &$object = null): Update {
		if (!isset($this->has_many[$member])) {
			throw new Semantics($this->class . "::memberQuery($member) called on non-many member");
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
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws SQLException
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws TableNotFound
	 */
	final public function memberForeignList(ORMBase $object, string $member): array {
		if (!isset($this->has_many[$member])) {
			throw new KeyNotFound(__CLASS__ . "::memberForeignList($member) called on non-many member");
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
	 * @throws SQLException
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws Semantics
	 */
	final public function memberForeignExists(ORMBase $object, string $member, mixed $id): bool {
		if (!isset($this->has_many[$member])) {
			throw new KeyNotFound(__CLASS__ . "::memberForeignExists($member) called on non-many member");
		}
		$many_spec = $this->hasMany($object, $member);
		$query = $this->hasManyQueryDefault($object, $many_spec, $member, true);
		$far_key = $many_spec['far_key'];
		$what = 'COUNT(' . $this->database()->sqlDialect()->columnAlias($far_key) . ')';
		return $query->addWhat('*X', $what)->addWhere($far_key, $id)->integer('X') !== 0;
	}

	/**
	 * @param ORMBase $object
	 * @param string $member
	 * @return array[]
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 */
	final public function memberForeignDelete(ORMBase $object, string $member): array {
		if (!isset($this->has_many[$member])) {
			throw new KeyNotFound(__METHOD__ . "($member) called on non-many member");
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
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 */
	final public function memberForeignAdd(ORMBase $this_object, string $member, ORMBase $link): array {
		$many_spec = $this->hasMany($this_object, $member);

		$class = $many_spec['class'];
		if (!$link instanceof $class) {
			throw new KeyNotFound($link::class . " is not an instanceof $class");
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
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @todo Remove dependencies on "table" use "link_class" instead
	 */
	private function hasManyInit(ORMBase $object, array $has_many): array {
		$class = $has_many['class'];
		$my_class = $this->class;
		$link_class = $has_many['link_class'] ?? null;
		if ($link_class) {
			$this->application->classes->register($link_class);
			$table = $this->application->ormRegistry($link_class)->table();
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
		if ($table === true) {
			// Clean up reference
			$has_many_object = $object->class_orm()->hasManyObject($object, $class);
			$table = $has_many_object['table'] ?? null;
			if (!is_string($table)) {
				throw new ClassNotFound($class, '{my_class} references table in {class}, but no table found for have_many', compact('my_class', 'class'));
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
	 * @param Base $set
	 * @return self
	 */
	final public function setDatabase(Base $set): self {
		$this->database = $set;
		$this->database_name = $set->codeName();
		$this->application->ormModule()->clearNamedCache($this->class);
		return $this;
	}

	/**
	 * Retrieve the database for this object
	 *
	 * @return Base
	 */
	final public function database(): Base {
		return $this->database;
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
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ORMNotFound
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
		} catch (ClassNotFound $e) {
			$schema = new FileSchema($this, $object, $sql);
			if ($schema->exists() || $schema->hasSQL()) {
				return $schema;
			}

			throw new ORMNotFound($this->class, 'Can not find schema for {class} in {searches}, or schema object {exception}', [
				'class' => $this->class, 'searches' => "\n" . implode("\n\t", $schema->searches()) . "\n",
				'exception' => $e,
			], $e);
		} catch (Throwable $e) {
			$this->application->hooks->call('exception', $e);

			throw new ORMNotFound($this->class, 'Schema error for {class} Exception: {previousClass}', [
				'previousClass' => $e::class,
			] + Exception::exceptionVariables($e), $e);
		}
	}

	/**
	 * @return void
	 * @throws ClassNotFound
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws NotFoundException
	 * @throws ParameterException
	 */
	protected function configureFromSQL(): void {
		$sqlScriptFile = $this->inheritSql(get_class($this));
		$this->configureFromSQLScript($this->database(), File::contents($sqlScriptFile), $sqlScriptFile);
	}

	/**
	 * @param Base $database
	 * @param string $sqlScript
	 * @param string $context
	 * @return void
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 */
	private function configureFromSQLScript(Base $database, string $sqlScript, string $context = ''): void {
		$dataType = $database->types();
		foreach ($database->splitSQLStatements($sqlScript) as $sqlStatement) {
			if ($database->parseSQLCommand($sqlStatement) === SQLParser::COMMAND_CREATE_TABLE) {
				$column_types = [];
				$table = $database->parseCreateTable($sqlStatement);
				$primary_keys = [];
				foreach ($table->columns() as $column) {
					/* @var $column Column */
					$sqlType = $column->sqlType();
					$column_types[$column->name()] = $dataType->native_type_to_data_type($sqlType);
					if ($column->isIndex(Index::TYPE_PRIMARY)) {
						$primary_keys[] = $column->name();
					}
				}
				$this->primary_keys = $primary_keys;
				$this->column_types = $column_types;
				return;
			}
		}

		throw new NotFoundException('No CREATE TABLE found in {context}: {sqlScript}', [
			'context' => $context, 'sqlScript' => $sqlScript,
		]);
	}

	/**
	 * @param string $class
	 * @return string
	 * @throws NotFoundException
	 */
	private function inheritSql(string $class): string {
		$subclasses = $this->application->classes->hierarchy($class);
		foreach ($subclasses as $subclass) {
			$sql = $this->application->autoloader->search($subclass, ['sql']);
			if ($sql) {
				return $sql;
			}
		}

		throw new NotFoundException('No SQL for {class} ({subclasses})', [
			'class' => $class, 'subclasses' => $subclasses,
		]);
	}

	/**
	 *
	 * @param ORMBase $object
	 * @return Schema
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ORMNotFound
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
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ORMNotFound
	 */
	public function schema(ORMBase $object): string|array|Schema {
		return $this->_database_schema($object);
	}

	/**
	 * Set up ->column_defaults so all members have values
	 */
	private function _columnDefaults(): void {
		foreach (array_keys($this->column_types) as $column) {
			if (array_key_exists($column, $this->column_defaults)) {
				continue;
			}
			$this->column_defaults[$column] = $this->_memberDefault($column);
		}
	}

	/**
	 * Take a database result and convert it into the internal data array
	 *
	 * @param ORMBase $object
	 * @param array $data
	 * @return array
	 * @throws ParseException
	 * @throws ParseException
	 * @throws Semantics
	 */
	final public function from_database(ORMBase $object, array $data): array {
		$column_types = $this->column_types;
		$data = $object->sql()->fromDatabase($object, $data);
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
	 * @throws ParseException
	 * @throws Semantics
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
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws Semantics
	 * @throws ORMNotFound
	 */
	final public function toDatabase(ORMBase $object, array $data, bool $insert = false): array {
		$data = $object->sql()->toDatabase($object, $data, $insert);
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
	 * @param string $type
	 * @return string|null
	 */
	private function _memberDefault(string $type): string|null {
		return match ($type) {
			self::TYPE_POLYMORPH => '',
			self::TYPE_CREATED, self::TYPE_MODIFIED => 'now',
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
		$class = StringTools::removePrefix($object::class, [$this->polymorphic . '_', $object::class, ]);
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
	 * @throws ParseException
	 * @throws Semantics
	 */
	private function memberFromDatabase(ORMBase $object, string $column, string $type, array &$data): void {
		$v = $data[$column];
		switch ($type) {
			case self::TYPE_REAL:
			case self::TYPE_FLOAT:
			case self::TYPE_DOUBLE:
			case self::TYPE_DECIMAL:
				if ($v === null) {
					break;
				}
				$data[$column] = Types::toFloat($v);

				break;
			case self::TYPE_TEXT:
			case self::TYPE_STRING:
				if ($v === null) {
					break;
				}
				$data[$column] = strval($v);

				break;
			case self::TYPE_HEX:
			case self::TYPE_HEX32:
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
				$data[$column] = Types::toInteger($v);

				break;
			case self::TYPE_BOOL:
				$data[$column] = Types::toBool($v);

				break;
			case self::TYPE_SERIALIZE:
				try {
					$data[$column] = empty($v) ? null : PHP::unserialize($v);
				} catch (SyntaxException) {
					$this->application->logger->error('unserialize of {n} bytes failed: {data}', [
						'n' => strlen($v), 'data' => substr($v, 0, 100),
					]);
					$data[$column] = null;
				}
				break;
			case self::TYPE_JSON:
				try {
					$data[$column] = empty($v) ? null : JSON::decode($v);
				} catch (ParseException $e) {
					$this->application->logger->error('Unable to parse JSON in {class}->{column} {json}', [
						'class' => $object::class, 'column' => $column, 'json' => $v,
					]);

					throw $e;
				}

				break;
			case self::TYPE_CREATED:
			case self::TYPE_MODIFIED:
			case self::TYPE_TIMESTAMP:
			case self::TYPE_DATETIME:
				$data[$column] = $v === '0000-00-00 00:00:00' || empty($v) ? null : Timestamp::factory($v);

				break;
			case self::TYPE_DATE:
				$data[$column] = $v === '0000-00-00' || empty($v) ? null : Date::factory($v);

				break;
			case self::TYPE_TIME:
				$data[$column] = empty($v) ? null : Time::factory($v);

				break;
			case self::TYPE_IP:
			case self::TYPE_IP4:
				$data[$column] = $v === null ? null : IPv4::fromInteger($v);

				break;
			case self::TYPE_POLYMORPH:
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
				throw new Semantics("Invalid column type $type");
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
	 * @throws ParseException
	 * @throws ParseException
	 * @throws Semantics
	 */
	private function memberFromArray(ORMBase $object, string $column, string $type, array &$data): void {
		switch ($type) {
			case self::TYPE_HEX:
			case self::TYPE_HEX32:
			case self::TYPE_SERIALIZE:
			case self::TYPE_JSON:
				break;
			case self::TYPE_IP:
			case self::TYPE_IP4:
				if (is_int($data[$column])) {
					$data[$column] = IPv4::fromInteger($data[$column]);
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
	 */
	private function sqlNow(ORMBase $object): string {
		$generator = $object->database()->sqlDialect();
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
	 * @return void
	 * @throws ParseException
	 * @throws Semantics
	 * @throws KeyNotFound
	 * @throws ORMNotFound
	 */
	final public function memberToDatabase(ORMBase $object, string $column, string $type, array &$data, bool $insert = false): void {
		if (!array_key_exists($column, $data)) {
			throw new Semantics('Can not call {orm}->member_to_database_twice on same column {column} {type} keys: {keys}', [
				'orm' => $object::class, 'column' => $column, 'type' => $type, 'keys' => array_keys($data),
			]);
		}
		$v = $data[$column];
		switch ($type) {
			case self::TYPE_POLYMORPH:
				$data[$column] = $this->polymorphicClassParse($object, $column);

				break;
			case self::TYPE_REAL:
			case self::TYPE_FLOAT:
			case self::TYPE_DOUBLE:
			case self::TYPE_DECIMAL:
				$data[$column] = $v === null || $v === '' ? null : floatval($v);

				break;
			case self::TYPE_TEXT:
			case self::TYPE_STRING:
				$data[$column] = $v === null ? null : strval($v);

				break;
			case self::TYPE_OBJECT:
				$data[$column] = $v === null ? null : ORMBase::mixedToID($v);

				break;
			case self::TYPE_CRC32:
				if (isset($this->crc_column)) {
					$data["*$column"] = 'CRC32(' . $this->database()->quoteName($object->member($this->crc_column)) . ')';
				}
				unset($data[$column]);

				break;
			case self::TYPE_HEX:
			case self::TYPE_HEX32:
				$data[$column] = Hexadecimal::decode($v);

				break;
			case self::TYPE_ID:
			case self::TYPE_INTEGER:
				$data[$column] = $v === null ? null : Types::toInteger($v, intval($v));

				break;
			case self::TYPE_BYTE:
				$data[$column] = Types::toInteger($v, $v) % 255;

				break;
			case self::TYPE_BOOL:
				$data[$column] = Types::toBool($v) ? 1 : 0;

				break;
			case self::TYPE_SERIALIZE:
				$data[$column] = serialize($v);

				break;
			case self::TYPE_JSON:
				$data[$column] = JSON::encode($v);

				break;
			case self::TYPE_CREATED:
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
			case self::TYPE_DATETIME:
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
			case self::TYPE_DATE:
				if (empty($v)) {
					$data[$column] = null;
				} elseif ($v === 'now') {
					unset($data[$column]);
					$data["*$column"] = $this->sqlNow($object);
				} elseif ($v instanceof Temporal) {
					$data[$column] = $v->format('{YYYY}-{MM}-{DD}');
				} elseif (is_numeric($v)) {
					$data[$column] = gmdate('Y-m-d', $v);
				}

				break;
			case self::TYPE_TIME:
				if (empty($v)) {
					$data[$column] = null;
				} elseif ($v === 'now') {
					unset($data[$column]);
					$data["*$column"] = $this->sqlNow($object);
				} elseif ($v instanceof Temporal) {
					$data[$column] = $v->format('{hh}:{mm}:{ss}');
				} elseif (is_numeric($v)) {
					$data[$column] = gmdate('H:i:s', $v);
				}

				break;
			case self::TYPE_IP4:
			case self::TYPE_IP:
				if ($v === null) {
					$data[$column] = 'NULL';
					return;
				}
				$gen = $this->database()->sqlDialect();
				$data["*$column"] = $gen->function_ip2long($gen->quoteText($v));
				unset($data[$column]);
				break;
			default:
				throw new Semantics("Invalid column type $type");
		}
	}

	/**
	 * Guess column types
	 *
	 * Updates internal $this->column_types
	 */
	private function _implyColumnTypes(): void {
		$data_type = $this->database()->types();
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
			} catch (ClassNotFound|KeyNotFound $e) {
				// WTF you need to not have this happen ever after configuration. TODO
				$this->application->logger->error($e);
			}
		}

		return $result;
	}
}
