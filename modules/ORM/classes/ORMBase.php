<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\ORM;

use Throwable;
use World\Class\Class_Base;
use zesk\Database_Exception_Connect;
use zesk\Database_Exception_NoResults;
use zesk\Exception_Class_NotFound;
use zesk\Exception_NotFound;
use zesk\Exception_Parse;
use zesk\Interface_Member_Model_Factory;

use zesk\Application;
use zesk\Database;
use zesk\PHP;
use zesk\Route;
use zesk\StringTools;
use zesk\Text;
use zesk\Timestamp;
use zesk\Model;
use zesk\JSON;
use zesk\ArrayTools;
use zesk\Database_SQL;

use zesk\Exception as zeskException;
use zesk\Exception_Configuration;
use zesk\Exception_Convert;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\Exception_Key;
use zesk\Interface_RouteArgument;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_Table_NotFound;

/**
 * Object Relational Mapping base class. Extend this class and Class_Base to create an ORM object.
 *
 * @todo Remove dependencies on $this->class->has_many and $this->class->has_one access
 * @author kent
 * @see Module_ORM
 * @see Class_Base
 */
class ORMBase extends Model implements Interface_Member_Model_Factory, Interface_RouteArgument {
	/**
	 * Boolean value which affects ORM::isNew() and ORM::register() which will not depend
	 * on the auto_column's presence to determine if an ORM is new or not.
	 * Will actually check
	 * the database. Allows you to have objects which normally would be created via auto-increment
	 * but instead allows you to create them specifically by ID. Usually used temporarily.
	 *
	 * Do not set this on a global basis via global ORM::ignore_auto_column=true as it will
	 * likely have catastrophic negative results on performance.
	 *
	 * @var string
	 */
	public const OPTION_IGNORE_AUTO_COLUMN = 'ignore_auto_column';

	/**
	 *
	 */
	public const OPTION_STORE_ERROR = 'store_error';

	/**
	 *
	 */
	public const OPTION_DUPLICATE_ERROR = 'duplicate_error';

	/**
	 * Previous call resulted in a new object retrieved from the database which exists
	 *
	 * @see ORMBase::register
	 * @var string
	 */
	public const STATUS_EXISTS = 'exists';

	/**
	 * Previous call resulted in the saving of the existing object in the database
	 *
	 * @see ORMBase::register
	 * @var string
	 */
	public const STATUS_INSERT = 'insert';

	/**
	 * Previous call failed or has an unknown result
	 *
	 * @see ORMBase::register
	 * @var string
	 */
	public const STATUS_UNKNOWN = 'failed';

	/**
	 * ORM debugging
	 *
	 * @var boolean
	 */
	public static bool $debug = false;

	/**
	 * Global state
	 *
	 * @var Application
	 */
	public Application $application;

	/**
	 * Initialize this value to an alternate object class name if you want more than one object to
	 * be represented by the same table or class configuration.
	 *
	 * e.g.
	 *
	 * <code>
	 * class Dog extends Cat {
	 * protected $class_name = "Cat";
	 * }
	 *
	 * @var string
	 */
	protected string $class_name = '';

	/**
	 * The object which governs the relationship with the database and other objects.
	 *
	 * e.g.
	 *
	 * <code>
	 * class Dog extends Cat {
	 * protected $class = "Cat";
	 * }
	 *
	 * @var Class_Base
	 */
	protected Class_Base $class;

	/**
	 * Flag to avoid Exception failures during flatten or during class
	 * configuration. If this is false then ->class is not set up yet so
	 * don't touch it.
	 *
	 * @var bool
	 */
	private bool $classValid = false;

	/**
	 * The leaf polymorphic class goes here
	 *
	 * @var string
	 */
	protected string $polymorphic_leaf = '';

	/**
	 * Database name where this object resides.
	 * If not specified, the default database.
	 * <code>
	 * protected $database = "tracker";
	 * </code>
	 *
	 * @var string
	 */
	protected string $database_name = '';

	/**
	 * Database object
	 * If not specified, the default database.
	 *
	 * @var Database
	 */
	private Database $database;

	/**
	 * Database table name
	 * <code>
	 * protected $table = "TArticleComment";
	 * </code>
	 *
	 * @var string
	 */
	protected string $table = '';

	/**
	 * When is_new requires a database query, cache it here
	 *
	 * @var boolean
	 */
	private ?bool $is_new_cached = null;

	/**
	 * When storing, set to true to avoid loops
	 *
	 * @var boolean
	 */
	protected bool $storing = false;

	/**
	 * Members of this object
	 *
	 * @var array
	 */
	protected array $members = [];

	/**
	 * List of things to do when storing
	 *
	 * @var array
	 */
	private array $store_queue = [];

	/**
	 * Does this object need to be loaded from the database?
	 *
	 * @var boolean
	 */
	private bool $need_load = true;

	/**
	 * Array of columns which I can store
	 */
	private array $store_columns = [];

	/**
	 * Result of register call
	 *
	 * @var string
	 */
	private string $status = '';

	/**
	 * When members is loaded, this is a copy to determine if changes have occurred.
	 *
	 * @var array
	 */
	private array $original;

	/**
	 * Retrieve user-configurable settings for this object
	 *
	 * @return array
	 */
	public static function settings(): array {
		return []; //TODO
	}

	/**
	 * Syntactic sugar - returns ORM not a Model
	 *
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return self
	 * @throws Exception_Class_NotFound
	 */
	public function ormFactory(string $class, mixed $mixed = null, array $options = []): self {
		$object = $this->modelFactory($class, $mixed, $options);
		assert($object instanceof ORMBase);
		return $object;
	}

	/**
	 * Create a new object
	 *
	 * @param Application $application
	 * @param mixed $mixed
	 *            Initializing value; either an id or an array of member names => values.
	 * @param array $options
	 *            List of Options to set before initialization
	 * @throws Database_Exception_Connect
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function __construct(Application $application, $mixed = null, array $options = []) {
		parent::__construct($application, null, $options);
		$this->inheritConfiguration();
		$this->initializeSpecification();
		$this->members = $this->class->column_defaults;
		$this->initialize($mixed, $this->option('initialize'));
		$this->setOption('initialize', null);
		$this->constructed();
	}

	/**
	 * Sleep functionality
	 */
	public function __sleep() {
		return array_merge(['members', ], parent::__sleep());
	}

	/**
	 * Wakeup functionality
	 */
	public function __wakeup(): void {
		$this->application = __wakeup_application();

		try {
			$this->initializeSpecification();
			$this->initialize($this->members, 'raw');
		} catch (Throwable $e) {
			PHP::log('during wakeup of ' . get_class($this));
			PHP::log($e);
		}
	}

	/**
	 * Retrieve an option from the class
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function class_option(string $name, mixed $default = null): mixed {
		return $this->class->option($name, $default);
	}

	/**
	 * Retrieve the Class_Base associated with this object.
	 * Often matches "Class_" . get_class($this), but not always.
	 *
	 * @return Class_Base
	 */
	public function class_orm(): Class_Base {
		return $this->class;
	}

	/**
	 * Retrieve the Class_Base associated with this object.
	 * Often matches "Class_" . get_class($this), but not always.
	 *
	 * @return Class_Base
	 */
	public function class_orm_name(): string {
		return $this->class_name;
	}

	/**
	 * All variables for this object (useful for translations, logging, and output)
	 *
	 * {@inheritdoc}
	 *
	 * @see Model::variables()
	 */
	public function variables(): array {
		$members = [];

		try {
			$members += $this->members();
		} catch (Throwable $e) {
			$members += ['membersException' => $e];
		}
		return $members + ArrayTools::prefixKeys($this->class->variables(), '_class_') + [
			'ormClass' => get_class($this), __CLASS__ . '::class' => get_class($this),
		];
	}

	/**
	 * @param string $mixed
	 * @param mixed|null $default
	 * @return mixed
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	public function get(string $mixed, mixed $default = null): mixed {
		return $this->has($mixed) ? $this->_get($mixed) : $default;
	}

	/**
	 *
	 * @param $mixed mixed
	 *            Model value to set
	 * @param $value mixed
	 *            Value to set
	 * @return ORMBase $this
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function set(string $mixed, mixed $value = null): self {
		$this->__set($mixed, $value);
		return $this;
	}

	/**
	 * Retrieve a list of class dependencies for this object
	 */
	public function dependencies(): array {
		return $this->class->dependencies($this);
	}

	/**
	 * Initialize per-object settings
	 * @return void
	 * @throws Database_Exception_Connect
	 * @throws Exception_Class_NotFound
	 * @throws Exception_NotFound
	 * @throws Exception_Semantics
	 */
	protected function initializeSpecification(): void {
		if (empty($this->class_name)) {
			if (isset($this->options['class_object'])) {
				$this->class_name = $this->options['class_object'];
				unset($this->options['class_object']);
				if (empty($this->class_name)) {
					throw new Exception_Semantics('{class} options [class_object] is blank', ['class' => get_class($this)]);
				}
			} else {
				$this->class_name = get_class($this);
			}
		}
		$this->classValid = false;
		$this->class = Class_Base::instance($this, [], $this->class_name);
		$this->classValid = true;
		if (!$this->database_name) {
			$this->database_name = $this->class->database_name;
		}
		$this->database = $this->application->databaseModule()->databaseRegistry($this->database_name, [
			'connect' => false,
		]);
		if (!$this->table) {
			$this->table = $this->class->table;
		}
		$this->store_columns = ArrayTools::keysFromValues(array_keys($this->class->column_types), true);
		$this->store_queue = [];
		$this->original = [];
	}

	/**
	 * Clean a code name to be without spaces or special characters. Numbers, digits, and - and _ are ok.
	 *
	 * @param string $name
	 * @param string $blank
	 * @return string
	 */
	public static function clean_code_name(string $name, string $blank = '-'): string {
		$codename = preg_replace('|[\s/]+|', '-', strtolower(trim($name, " \t\n$blank")));
		$codename = preg_replace('/[^-_A-Za-z0-9]/', '', $codename);
		if ($blank !== '-') {
			$codename = strtr($codename, '-', $blank);
		}
		return $codename;
	}

	/**
	 *
	 * @return ?Schema
	 * @throws Exception_ORMNotFound
	 */
	final public function database_schema(): ?Schema {
		return $this->class->database_schema($this);
	}

	/**
	 *
	 * @throws Exception_ORMNotFound
	 */
	public function schema(): Schema|array|string|null {
		return $this->class->schema($this);
	}

	/**
	 * Call when the schema of an object has changed and needs to be refreshed
	 * @throws Exception_Semantics
	 */
	public function schemaChanged(): void {
		$this->class->schemaChanged();
	}

	/**
	 *
	 * @return Database
	 */
	public function database(): Database {
		return $this->database;
	}

	/**
	 * @param Database $database
	 * @return $this
	 */
	public function setDatabase(Database $database): self {
		$this->database = $database;
		$this->database_name = $database->codeName();
		return $this;
	}

	/**
	 *
	 * @return Database_SQL
	 */
	public function sql(): Database_SQL {
		return $this->database()->sql();
	}

	/**
	 *
	 * @return string
	 */
	final public function table(): string {
		return $this->table !== '' ? $this->table : $this->class->table;
	}

	/**
	 *
	 * @return boolean
	 */
	public function tableExists(): bool {
		return $this->database()->tableExists($this->table());
	}

	/**
	 * Default implementation of the object name
	 */
	public function name(): string {
		$name_col = $this->nameColumn();
		if (empty($name_col)) {
			return '';
		}

		try {
			return $this->_get($name_col);
		} catch (Throwable) {
			return '';
		}
	}

	/**
	 * Retrieve the name column for this object (if any)
	 *
	 * @return string|null
	 */
	final public function nameColumn(): string|null {
		return $this->class->name_column;
	}

	/**
	 * Retrieves the single find key for an object, if available.
	 *
	 * @return string|null
	 */
	final public function findKey(): ?string {
		$keys = $this->class->find_keys;
		if (is_array($keys) && count($keys) === 1) {
			return $keys[0];
		}
		return null;
	}

	/**
	 * Retrieve list of member names used to find an object in the database
	 *
	 * @return array:string
	 */
	final public function findKeys(): array {
		return $this->class->find_keys;
	}

	/**
	 * Retrieve list of member names used to find a duplicate object in the database
	 *
	 * @return array:string
	 */
	final public function duplicateKeys(): array {
		return $this->class->duplicate_keys;
	}

	/**
	 * Returns valid member names for this database table
	 *
	 * Includes dynamic fields including iterators and has_one/has_many/getters/setters
	 *
	 * @return array
	 */
	public function memberNames(): array {
		return $this->class->memberNames();
	}

	/**
	 * Return just database columns for this object
	 *
	 * @return array
	 */
	public function columns(): array {
		return array_keys($this->class->column_types);
	}

	/**
	 * Name of this object's class (where is this used?)
	 *
	 * @return string
	 */
	public function className(): string {
		return $this->class->name;
	}

	/**
	 * If there's an ID column, return the name of the column
	 *
	 * @return ?string
	 */
	public function idColumn(): ?string {
		return $this->class->id_column;
	}

	/**
	 * Does this object have all primary keys set to a value?
	 *
	 * @return boolean
	 */
	public function hasPrimaryKeys(): bool {
		$pk = $this->class->primary_keys;
		if (count($pk) === 0) {
			return false;
		}
		foreach ($pk as $primary_key) {
			try {
				$v = $this->member($primary_key);
			} catch (Throwable) {
				return false;
			}
			if (empty($v)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * List of primary keys for this object
	 *
	 * @return array
	 */
	public function primaryKeys(): array {
		return $this->class->primary_keys;
	}

	/**
	 * Always use UTC timestamps when setting dates for this object
	 *
	 * @return boolean
	 */
	public function utcTimestamps(): bool {
		return $this->class->utc_timestamps;
	}

	/**
	 * @return Database
	 */
	public function selectDatabase(): Database {
		return $this->database()->selectDatabase($this->databaseName());
	}

	/**
	 * Ensure this object is loaded from database if needed
	 * @return self
	 * @throws Exception_ORMNotFound
	 */
	public function refresh(): self {
		if ($this->need_load && $this->canFetch()) {
			try {
				$this->fetch();
				$this->need_load = false;
			} catch (Throwable $e) {
				throw new Exception_ORMNotFound(get_class($this), $e->getMessage(), zeskException::exceptionVariables($e), $e);
			}
		}
		return $this;
	}

	/**
	 * ORM initialization; when creating an object this should be called using two methods: An
	 * integer ID for this object, or an array of populated values, or from the database itself
	 *
	 * @param array $mixed
	 * @param mixed $initialize
	 * @return void
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	private function initializeMembers(array $mixed, mixed $initialize = false): void {
		$this->_initialized = count($mixed) !== 0;
		if ($initialize === true) { // Means from database
			$mixed = $this->class->from_database($this, $mixed);
			$this->is_new_cached = false;
		} elseif ($initialize !== 'raw') {
			$mixed = $this->class->from_array($this, $mixed);
		}
		$this->original = $this->toDatabase($mixed);
		$this->members = $mixed + $this->members;
		$this->need_load = false;
	}

	/**
	 * @return void
	 */
	private function initializeDefaults(): void {
		$this->_initialized = false;
		$this->members = $this->class->column_defaults;
		$this->original = [];
		$this->need_load = true;
	}

	/**
	 * @param mixed $id
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	private function initializeId(int|string|array $id): void {
		if ($this->class->id_column !== null) {
			$this->setId($id);
			$this->_initialized = true;
			$this->original = [];
			$this->need_load = true;
		} else {
			throw new Exception_Semantics(get_class($this) . ' initialized with single value but no id column');
		}
	}

	/**
	 * ORM initialization; when creating an object this should be called using two methods: An
	 * integer ID for this object, or an array of populated values, or from the database itself
	 *
	 * @param mixed $mixed
	 * @param mixed $initialize
	 * @return $this
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function initialize(mixed $mixed, mixed $initialize = false): self {
		$this->is_new_cached = null;
		$this->store_queue = [];
		if (is_array($mixed)) {
			$this->initializeMembers($mixed, $initialize);
		} elseif ($mixed !== null) {
			$this->initializeId($mixed);
		} else {
			$this->initializeDefaults();
		}
		if (!$this->need_load) {
			$this->callHook('initialized');
		}
		return $this;
	}

	/**
	 * ORMBase called at the end of __construct, always
	 *
	 * @return void
	 */
	protected function constructed(): void {
		// pass
	}

	/**
	 * @return bool
	 */
	public function initializing(): bool {
		return !$this->classValid;
	}

	/**
	 * @param bool $isNew
	 * @return $this
	 */
	public function setIsNew(bool $isNew): self {
		$this->is_new_cached = $isNew;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isNew(): bool {
		if (is_bool($this->is_new_cached)) {
			return $this->is_new_cached;
		}

		try {
			$auto_column = $this->class->auto_column;
			if ($auto_column && !$this->optionBool(self::OPTION_IGNORE_AUTO_COLUMN)) {
				$auto = $this->member($auto_column);
				return empty($auto);
			}
			if (count($pk = $this->class->primary_keys) > 0) {
				if ($this->memberIsEmpty($pk)) {
					return true;
				}
				$where = $this->members($pk);
				$sql = $this->sql()->select([
					'what' => ['*X' => 'COUNT(*)', ], 'tables' => $this->table(), 'where' => $where,
				]);
				$this->is_new_cached = !toBool($this->database()->queryInteger($sql, 'X'));
				return $this->is_new_cached;
			}
		} catch (Throwable $t) {
			$this->application->logger->error(__METHOD__ . ' threw {exceptionClass} {message}', zeskException::exceptionVariables($t));
		}
		return true; // Always new
	}

	/**
	 * Empty out this object's members and set to defaults
	 *
	 * @return ORMBase
	 */
	public function clear(): self {
		$this->members = $this->class->column_defaults;
		$this->store_queue = [];
		return $this;
	}

	/**
	 * Return the display name for this object. Override in subclasses for custom behavior.
	 *
	 * @return string
	 */
	public function displayName(): string {
		$name_column = $this->class->name_column;
		if (!$name_column) {
			return '';
		}

		try {
			return strval($this->member($name_column));
		} catch (Throwable) {
			return '';
		}
	}

	/**
	 * Get/set the ID for this object
	 *
	 * @param int|string|array $set
	 * @return $this
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function setId(int|string|array $set): self {
		$id_column = $this->class->id_column;
		if (is_string($id_column)) {
			return $this->set($id_column, $set);
		}

		/**
		 * Passing a string or list of values to load
		 */
		$pk = $this->class->primary_keys;
		if (is_string($set) || ArrayTools::isList($set)) {
			$ids = toList($set);
			if (count($ids) !== count($pk)) {
				throw new Exception_Parameter('Need {keyCount} primary keys: {primaryKeys}, passed {actualCount} {actualKeys}', [
					'keyCount' => count($pk), 'primaryKeys' => $pk, 'actualCount' => count($ids), 'actualKeys' => $ids,
				]);
			}
			foreach ($pk as $index => $k) {
				$this->setMember($k, $ids[$index]);
			}
			return $this;
		}
		/**
		 * Passing an array of primary keys (hopefully)
		 */
		if (is_array($set)) {
			$missing = [];
			foreach ($pk as $k) {
				if (array_key_exists($k, $set)) {
					$this->set($k, $set);
				} else {
					$missing[] = $k;
				}
			}
			if (count($missing) > 0) {
				$this->application->logger->warning('{class}::id("{set}") missing primary keys: {k}', [
					'class' => get_class($this), 'set' => JSON::encode($set), 'ks' => implode(',', $missing),
				]);
			}
			$this->setMembers($set);
			return $this;
		}

		throw new Exception_Parameter('{class}::id("{value}" {type}) unknown parameter: ', [
			'class' => get_class($this), 'value' => _dump($set), 'type' => type($set),
		]);
	}

	/**
	 * @return int|string|array
	 * @throws Exception_ORMEmpty
	 */
	public function id(): int|string|array {
		$id_column = $this->class->id_column;
		/**
		 * Single ID
		 */
		if ($id_column !== '') {
			// TODO Move this into member classes
			$id = $this->members[$id_column] ?? null;
			if ($id instanceof ORMBase) {
				return $id->id();
			}
			assert(array_key_exists($id_column, $this->class->column_types));
			$type = $this->class->column_types[$id_column];
			return $type === Class_Base::TYPE_ID || $type === Class_Base::TYPE_INTEGER ? intval($id) : strval($id);
		}
		/**
		 * No ID columns
		 */
		if (count($pk = $this->class->primary_keys) === 0) {
			return [];
		}
		/**
		 * Multiple ID columns
		 */
		try {
			return $this->members($pk);
		} catch (Throwable $t) {
			throw new Exception_ORMEmpty(self::class, 'Fetching ID with {pk}', ['pk' => $pk], $t);
		}
	}

	/**
	 * Returns name of the database used by this object
	 *
	 * @return string
	 * @see ORMBase::databaseName()
	 */
	public function databaseName(): string {
		return $this->database_name;
	}

	/**
	 * Retrieve a query for the current object
	 *
	 * @param string $alias
	 * @return Database_Query_Select
	 */
	public function querySelect(string $alias = ''): Database_Query_Select {
		$query = new Database_Query_Select($db = $this->database());
		$query->setORMClass(get_class($this))->setFactory($this)->setORMClassOptions($this->inheritOptions());
		if (empty($alias)) {
			$alias = 'X';
		}
		return $query->from($this->table(), $alias)->setWhatString($db->sql()->columnAlias('*', $alias));
	}

	/**
	 * Create an insert query for this object
	 *
	 * @return Database_Query_Insert
	 */
	public function queryInsert(): Database_Query_Insert {
		$query = new Database_Query_Insert($this->database());
		$query->setORMClass(get_class($this));
		$query->setORMClassOptions($this->inheritOptions());
		return $query->setInto($this->table())->setValidColumns($this->columns());
	}

	/**
	 * Create an insert -> select query for this object
	 *
	 * @param string $alias
	 * @return Database_Query_Insert_Select
	 */
	public function queryInsertSelect(string $alias = ''): Database_Query_Insert_Select {
		$query = new Database_Query_Insert_Select($this->database());
		$query->setORMClass(get_class($this));
		$query->setORMClassOptions($this->inheritOptions());
		$query->from($this->table(), $alias);
		return $query->into($this->table());
	}

	/**
	 * Create an update query for this object
	 *
	 * @param string $alias
	 * @return Database_Query_Update
	 */
	public function queryUpdate(string $alias = ''): Database_Query_Update {
		$query = new Database_Query_Update($this->database());
		$query->setORMClassOptions($this->inheritOptions());
		return $query->setORMClass(get_class($this))->setTable($this->table(), $alias)->setValidColumns($this->columns(), $alias);
	}

	/**
	 * Create delete query for this object
	 *
	 * @return Database_Query_Delete
	 */
	public function queryDelete(): Database_Query_Delete {
		$query = new Database_Query_Delete($this->database());
		return $query->setORMClass(get_class($this))->setORMClassOptions($this->inheritOptions());
	}

	/**
	 * Retrieve an iterator for the current object
	 *
	 * @param Database_Query_Select $query
	 * @param array $options
	 * @return ORMIterator
	 * @throws Exception_Class_NotFound
	 */
	public function iterator(Database_Query_Select $query, array $options = []): ORMIterator {
		$class = $options['iterator_class'] ?? ORMIterator::class;
		$object = $this->application->factory($class, get_class($this), $query, $this->inheritOptions() + $options);
		assert($object instanceof ORMIterator);
		return $object;
	}

	/**
	 * Iterate on an object's member
	 *
	 * @param $member string
	 *            Many member
	 * @param $where mixed
	 *            Optional where query
	 * @return ORMIterator
	 * @throws Exception_Class_NotFound - remove this by preflighting
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics - remove this by preflighting
	 */
	protected function memberIterator(string $member, array $where = []): ORMIterator {
		$has_many = $this->class->hasMany($this, $member);
		if (!$this->hasPrimaryKeys()) {
			throw new Exception_Key('Can not iterate on an uninitialized object {class}', [
				'class' => get_class($this),
			]);
		}
		$object = null;
		$query = $this->memberQuery($member, $object);
		if ($where) {
			$query->appendWhere(ArrayTools::prefixKeys($where, $query->alias() . '.'));
		}
		/*
		 * @var $object ORM
		 */
		$iterator = $object->iterator($query, ['class' => $has_many['iterator_class'] ?? null] + $this->inheritOptions());
		if (!array_key_exists('link_class', $has_many)) {
			$iterator->setParent($this, $has_many['foreign_key']);
		}
		return $iterator;
	}

	/**
	 * Create a query for an object's member.
	 * The alias for the target table is the name of the member.
	 *
	 * So $object->memberQuery("dogs") the alias is "dogs" so use "dogs.column" in the query.
	 *
	 * @param $member string
	 *            Many member
	 * @param ORMBase|null $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Select
	 * @throws Exception_Class_NotFound - remove this by preflighting
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics - remove this by preflighting
	 */
	public function memberQuery(string $member, ORMBase &$object = null): Database_Query_Select {
		return $this->class->memberQuery($this, $member, $object);
	}

	/**
	 * Create a query for an object's member
	 *
	 * @param $member string
	 *            Many member
	 * @param ORMBase|null $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Update
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 * @todo Unimplemented
	 */
	public function memberQueryUpdate(string $member, ORMBase &$object = null): Database_Query_Update {
		return $this->class->memberQueryUpdate($this, $member, $object);
	}

	/**
	 *
	 * @param string $member
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	private function memberForeignList(string $member): array {
		if ($this->isNew()) {
			return array_keys(toArray($this->members[$member] ?? []));
		}
		return $this->class->memberForeignList($this, $member);
	}

	/**
	 * @param string $member
	 * @param int|string|array $id
	 * @return bool
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	private function memberForeignExists(string $member, int|string|array $id): bool {
		if ($this->isNew()) {
			return apath($this->members, [$member, $id, ]) !== null;
		}
		return $this->class->memberForeignExists($this, $member, $id);
	}

	/**
	 * @param string $member
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 */
	private function memberForeignDelete(string $member): void {
		$queue = $this->class->memberForeignDelete($this, $member);
		$this->store_queue += $queue;
		$this->members[$member] = [];
	}

	/**
	 * @param $member
	 * @param ORMBase $object
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_ORMEmpty
	 */
	private function memberForeignAdd($member, ORMBase $object): void {
		$foreign_keys = $object->members($object->primaryKeys());
		$hash = json_encode($foreign_keys);
		$this->members[$member][$hash] = $object;
		$this->store_queue += $this->class->memberForeignAdd($this, $member, $object);
	}

	/**
	 * @param $table
	 * @param $foreign_key
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_ORMEmpty
	 */
	private function _fk_delete($table, $foreign_key): void {
		$sql = $this->sql()->delete($table, [$foreign_key => $this->id()]);
		$this->database()->query($sql);
	}

	/**
	 * @param ORMBase $object
	 * @param string $update_key
	 * @return void
	 * @throws Exception_Store
	 */
	private function _fk_store(ORMBase $object, string $update_key): void {
		try {
			$object->set($update_key, $this->id())->store();
		} catch (zeskException $e) {
			throw new Exception_Store($object::class, '{ormClass}::_fk_store({updateKey}) - {exceptionClass} {message}', [
				'ormClass' => $object::class, 'update_key' => $update_key,
			] + $e->variables(), $e);
		}
	}

	/**
	 * @param ORMBase $object
	 * @param string $table
	 * @param array $replace
	 * @return void
	 * @throws Exception_Store
	 */
	private function _fk_link_store(ORMBase $object, string $table, array $replace): void {
		try {
			if ($object->isNew() || $object->changed()) {
				$object->store();
			}
			$map = ['Foreign' => $this->id(), 'Far' => $object->id(), ];
			$replace = map($replace, $map);
			$this->database()->replace($table, $replace);
		} catch (zeskException $e) {
			throw new Exception_Store($object::class, '{ormClass}::_fk_store({table}, {replace}) - {exceptionClass} {message}', [
				'ormClass' => $object::class, 'table' => $table, 'replace' => $replace,
			] + $e->variables(), $e);
		}
	}

	/**
	 * Return the list of options inherited to clones or sub-objects of this object
	 *
	 * @return array
	 */
	public function inheritOptions(): array {
		if ($this->class->inherit_options) {
			return $this->options($this->class->inherit_options);
		}
		return [];
	}

	/**
	 * Retrieve the original value of an object's member prior to modifying in memory and before
	 * storing
	 *
	 * @param string $member
	 * @return mixed
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	protected function original(string $member): mixed {
		$save = $this->members;
		$this->members = $this->original;
		$result = $this->_get($member);
		$this->members = $save;
		return $result;
	}

	/**
	 * Whenever an object attached to this object is requested, this method is called.
	 *
	 * Override in subclasses to get special behavior.
	 *
	 * @param string $member
	 *            Name of the member we are fetching
	 *
	 * @param string $class
	 *            Class of member
	 * @param string $mixed
	 *            Current data stored in member
	 * @param array $options
	 *            Options to create when creating object
	 * @return ORMBase
	 * @throws Exception_Class_NotFound
	 */
	public function memberModelFactory(string $member, string $class, mixed $mixed = null, array $options = []): ORMBase {
		return $this->ormFactory($class, $mixed, $options); //->refresh();
	}

	/**
	 *
	 * @param Exception_ORMNotFound $e
	 * @param string|null $member
	 * @param mixed|null $data
	 * @return void
	 * @throws Exception_ORMNotFound
	 */
	private function orm_not_found_exception(Exception_ORMNotFound $e, string $member = null, mixed $data = null): void {
		if ($this->optionBool('fix_orm_members') || $this->optionBool('fix_member_objects')) {
			try {
				// Prevent infinite recursion
				$magic = '__' . __METHOD__;
				if ($this->members[$magic] ?? false) {
					return;
				}
				$this->original[$magic] = true;
				$this->members[$magic] = true;
				$application = $this->application;
				$application->hooks->call('exception', $e);
				$application->logger->error("Fixing not found {member} {member_class} (#{data}) in {class} (#{id})\n{bt}", [
					'member' => $member, 'member_class' => $this->class->has_one[$member] ?? '-no-has-one-',
					'data' => $data, 'class' => get_class($this), 'id' => $this->id(), 'bt' => _backtrace(),
				]);
				if ($member) {
					$this->members[$member] = null;
				}
				$this->store();
				unset($this->original[$magic]);
				unset($this->members[$magic]);
			} catch (\Exception $oh_no) {
				$application->logger->critical('Exception while doing ORM Not found:{class} {message} ', Exception::exceptionVariables($oh_no));
			}
		} else {
			throw $e;
		}
	}

	/**
	 * Retrieve a member which is another ORM
	 *
	 * @param string $member
	 * @param array $options
	 * @return self
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 */
	final protected function memberObject(string $member, array $options = []): self {
		$this->refresh();

		if (!array_key_exists($member, $this->members)) {
			throw new Exception_Key($member);
		}
		$data = $this->members[$member];
		if ($data === null) {
			throw new Exception_ORMEmpty("$member is null");
		}
		if (!array_key_exists($member, $this->class->has_one)) {
			throw new Exception_Key('Accessing {class}::member_object but {member} is not in has_one', [
				'class' => get_class($this), 'member' => $member,
			]);
		}
		$class = $this->class->has_one[$member];
		if ($class[0] === '*') {
			$class = $this->member(substr($class, 1));
		}
		if ($data instanceof $class) {
			return $data;
		}
		if ($this->optionBool('debug')) {
			$this->application->logger->debug('Loading {class} member {member} with id {data}', [
				'class' => get_class($this), 'member' => $member, 'data' => $data,
			]);
		}

		try {
			$object = $this->memberModelFactory($member, $class, $data, $options + $this->inheritOptions());
			$this->members[$member] = $object;
			return $object;
		} catch (Throwable $e) {
			throw new Exception_ORMNotFound($class, $e->getMessage(), [], $e);
		}
	}

	/**
	 * Does this object have a member value?
	 *
	 * @param string $member
	 * @return bool
	 */
	public function has(string $member): bool {
		// Need to check $this->members to handle listing an object with additional query fields which may not be configured in the base object
		// Prevents ->defaults() from setting the value to null if it's in there
		return $this->hasMember($member) || array_key_exists($member, $this->members) || isset($this->class->has_many[$member]);
	}

	/**
	 * Get member using getter, has_many, has_one, or a regular typed member. Internal only
	 *
	 * @param string $member
	 * @return mixed
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	protected function _get(string $member): mixed {
		if (!$this->classValid) {
			return null;
		}
		if (($method = ($this->class->getters[$member] ?? null)) !== null) {
			assert(method_exists($this, $method));
			return call_user_func_array([$this, $method, ], [$member, ]);
		}
		if (array_key_exists($member, $this->class->has_many)) {
			if (array_key_exists($member, $this->members)) {
				return $this->members[$member];
			}
			$many = $this->class->has_many[$member];
			return $this->memberIterator($member, $many['iterator_where'] ?? []);
		}
		if (array_key_exists($member, $this->class->has_one)) {
			return $this->memberObject($member, $this->inheritOptions());
		}
		return $this->member($member);
	}

	/**
	 * May be overridden in subclasses to abstract away model.
	 *
	 * @param string $key
	 * @return mixed
	 */
	/**
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed {
		try {
			return $this->_get($key);
		} catch (\Exception) {
			return null;
		}
	}

	/**
	 * @param string $key
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function __unset(string $key): void {
		if (array_key_exists($key, $this->class->has_many)) {
			$this->memberForeignDelete($key);
			$this->members[$key] = [];
			return;
		}

		$this->memberRemove($key);
	}

	/**
	 * Lookup the current ORM using find_keys and the value supplied here.
	 *
	 * Returns a new ORM with loaded values
	 *
	 * @param int|string|array $value
	 * @return $this
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function memberFind(int|string|array $value): self {
		if (is_string($value) || is_int($value)) {
			$find_keys = $this->class->find_keys;
			if (count($find_keys) === 1) {
				$value = [$find_keys[0] => $value, ];
			} else {
				throw new Exception_Parameter('ORM {class} has multiple find keys {keys}, memberFind requires array parameter', [
					'class' => get_class($this), 'keys' => $find_keys,
				]);
			}
		}
		assert(is_array($value));
		return $this->duplicate()->setMembers($value)->find();
	}

	/**
	 * @param int|string $key
	 * @return bool
	 */
	public function __isset(int|string $key): bool {
		if (array_key_exists($key, $this->class->has_many)) {
			return true;
		}
		return isset($this->members[$key]);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function __set(string $key, mixed $value): void {
		if (($method = $this->class->setters[$key] ?? null) !== null) {
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("ORM setter \"$method\" for " . get_class($this) . ' does not exist');
			}
			call_user_func_array([$this, $method, ], [
				$value, $key,
				/* Allow simple case to be written more easily */
			]);
			return;
		}
		if (array_key_exists($key, $this->class->has_many)) {
			if (is_array($value)) {
				$this->__unset($key);
				foreach ($value as $v) {
					$this->__set($key, $v);
				}
				return;
			}
			if (!$value instanceof ORMBase) {
				if ($value === null) {
					$this->memberForeignDelete($key);
					return;
				}
				$value = $this->ormFactory($this->class->has_many[$key]['class'], $value);
			}
			$this->memberForeignAdd($key, $value);
			return;
		}
		if (array_key_exists($key, $this->class->has_one)) {
			$class = $this->class->has_one[$key];
			$dynamic_member = $class[0] === '*' ? substr($class, 1) : null;
			if ($value instanceof ORMBase) {
				if ($dynamic_member) {
					$this->setMember($dynamic_member, $value::class);
				}
			} elseif ($value !== null) {
				if ($dynamic_member) {
					$class = $this->member($dynamic_member);
					if (empty($class)) {
						throw new Exception_Semantics('Must set member {member} with class before using non-ORM __set on class {class} with value {value}', [
							'member' => $dynamic_member, 'class' => get_class($this), 'value' => $value,
						]);
					}
				}
				$object = $this->ormFactory($class);

				try {
					$found = $object->memberFind($value);
					$this->setMember($key, $found);
				} catch (Exception_ORMNotFound) {
					return;
				}
			}
		}
		$this->setMember($key, $value);
		$this->_initialized = true;
	}

	/**
	 * @param string $member
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	public function links(string $member): array {
		if (array_key_exists($member, $this->class->has_many)) {
			return $this->memberForeignList($member);
		}

		throw new Exception_Key('No such links {member} in {class}', [
			'member' => $member, 'class' => get_class($this),
		]);
	}

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return bool
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	public function isLinked(string $member, mixed $value): bool {
		if (array_key_exists($member, $this->class->has_many)) {
			return $this->memberForeignExists($member, $value);
		}
		return false;
	}

	/**
	 * Retrieve a member as a boolean value
	 *
	 * @param $member string Name of member
	 * @return boolean
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function memberBool(string $member): bool {
		$this->refresh();
		return toBool($this->member($member));
	}

	/**
	 * Retrieve a member as a timestamp value
	 *
	 * @param string $member
	 * @return Timestamp
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function memberTimestamp(string $member): Timestamp {
		$this->refresh();
		$value = $this->member($member);
		if ($value instanceof Timestamp) {
			return $value;
		}
		return Timestamp::factory($value);
	}

	/**
	 * Retrieve a member as an integer
	 * @param string $member
	 * @return int
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_ORMEmpty
	 */
	public function memberInteger(string $member): int {
		$this->refresh();
		$value = $this->member($member);
		if (is_numeric($value)) {
			return intval($value);
		}
		if ($value instanceof ORMBase && $value->idColumn()) {
			$id = $value->id();
			assert(is_numeric($id));
			return intval($id);
		}

		throw new Exception_Convert('Unable to convert {value} of {type} to integer', [
			'value' => $value, 'type' => type($value),
		]);
	}

	/**
	 * Retrieve a member of this object. Note that you can set member values in an ORMs definition
	 * as many database operations allow this; but the value is required to be populated as
	 * a member prior to retrieval
	 *
	 * @param string $member Field to retrieve
	 * @return mixed
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function member(string $member): mixed {
		if (!in_array($member, $this->class->primary_keys)) {
			$this->refresh();
		}
		if (array_key_exists($member, $this->members)) {
			return $this->members[$member];
		}
		return $this->class->memberDefault($member);
	}

	/**
	 * Fetch the raw member
	 *
	 * @param string $member
	 * @param mixed $def
	 * @return mixed
	 */
	protected function raw_member(string $member, mixed $def = null): mixed {
		return $this->members[$member] ?: $def;
	}

	/**
	 * @param string $member
	 * @param array $data
	 * @param bool $append
	 * @return $this
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function setMemberData(string $member, array $data, bool $append = false): self {
		$existing = $this->memberData($member);
		return $this->setMember($member, $append ? $existing + $data : $data);
	}

	/**
	 * Get member as an array
	 *
	 * @param string $member
	 * @return array
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function memberData(string $member): array {
		return toArray($this->member($member));
	}

	/**
	 * Have any of the members given changed in this object?
	 *
	 * @param mixed $members
	 *            Array or list of members
	 * @return boolean
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	public function membersChanged(string|array $members): bool {
		$current = [];
		foreach (toList($members) as $member) {
			try {
				$current[$member] = $this->member($member);
			} catch (Exception_ORMNotFound) {
				$current[$member] = null;
			}
		}
		$column_types = $this->class->column_types;
		foreach (toList($members) as $column) {
			if (array_key_exists($column, $column_types)) {
				$this->class->memberToDatabase($this, $column, $column_types[$column], $current);
			}
			if (($this->original[$column] ?? null) !== ($current[$column] ?? null)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Did anything change in this object? If no parameters are passed, determines if any
	 * database member has changed.
	 *
	 * Does not include changes to ORM members other than ID changes.
	 *
	 * @param array|string $members List of members to test for changes
	 * @return boolean
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	public function changed(array|string $members = ''): bool {
		return $this->membersChanged($members === '' ? $this->columns() : $members);
	}

	/**
	 * Retrieve the changes to this object as an array of member => array("old value", "new value").
	 *
	 *
	 * @return array
	 */
	public function changes(): array {
		$changes = [];
		foreach ($this->columns() as $k) {
			try {
				if ($this->membersChanged($k)) {
					$changes[$k] = [$this->original[$k] ?? null, $this->members[$k] ?? null];
				}
			} catch (Throwable) {
			}
		}
		return $changes;
	}

	/**
	 * Passing in NULL for $mixed will fetch ALL members, including those which may be "extra" as returned by a custom query, for example.
	 *
	 * @param array|string|null $mixed
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	public function members(array|string $mixed = null): array {
		$this->refresh();
		return $this->rawMembers($mixed);
	}

	/**
	 * @param array|string|null $mixed
	 * @return array
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function rawMembers(array|string $mixed = null): array {
		if (is_string($mixed)) {
			$mixed = toList($mixed);
			$result = [];
		} elseif ($mixed === null) {
			$mixed = array_keys($this->class->column_types);
			$result = $this->members; // Start with all members, overwrite ones which have getters/setters here
		} else {
			$result = [];
		}
		foreach ($mixed as $member) {
			try {
				$result[$member] = $this->_get($member);
			} catch (Exception_ORMNotFound|Exception_ORMEmpty) {
				$result[$member] = null; // TODO Maybe use a dummy, empty object? or a NULL ORM? 2018-03 KMD
			}
		}
		return $result;
	}

	/**
	 * Returns true if the member is empty
	 * For multiple members, returns true if ANY member is empty
	 * For multiple members, returns false if no members are passed in
	 *
	 * @param string|array $member
	 * @return boolean
	 */
	public function memberIsEmpty(string|array $member): bool {
		if (is_array($member)) {
			foreach ($member as $m) {
				if ($this->memberIsEmpty($m)) {
					return true;
				}
			}
			return false;
		}

		try {
			$this->refresh();
		} catch (Throwable) {
			return true;
		}
		$d = $this->raw_member($member);
		return empty($d);
	}

	/**
	 * Set a member to a value. You can add any member to an ORM.
	 *
	 * @param string $member
	 * @param mixed $value
	 * @param boolean $overwrite
	 * @return $this
	 */
	public function setMember(string $member, mixed $value = null, bool $overwrite = true): self {
		try {
			$this->refresh();
		} catch (Exception_ORMNotFound) {
		}
		if ($overwrite || !array_key_exists($member, $this->members)) {
			if ($member === $this->class->auto_column || in_array($member, $this->class->primary_keys)) {
				$this->is_new_cached = null;
			}
			$this->members[$member] = $value;
		}
		return $this;
	}

	/**
	 * @param array $members
	 * @param bool $overwrite
	 * @return $this
	 */
	public function setMembers(array $members, bool $overwrite = true): self {
		foreach ($members as $key => $value) {
			$this->setMember($key, $value, $overwrite);
		}
		return $this;
	}

	/**
	 * Returns value before resetting to default value.
	 *
	 * @param string $member
	 * @return mixed
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function memberRemove(string $member): mixed {
		$data = $this->member($member);
		unset($this->members[$member]);
		$this->members[$member] = $this->class->memberDefault($member);
		return $data;
	}

	/**
	 * Change the status of the store column structure
	 *
	 * @param string $member
	 * @return bool
	 * @throws Exception_Key
	 */
	private function _storeMember(string $member): bool {
		if (!array_key_exists($member, $this->store_columns)) {
			throw new Exception_Key($member);
		}
		return $this->store_columns[$member];
	}

	/**
	 * Change the status of the store column structure
	 *
	 * @param string $member
	 * @param bool $store
	 * @return void
	 * @throws Exception_Key
	 */
	private function _setStoreMember(string $member, bool $store): void {
		if (!array_key_exists($member, $this->store_columns)) {
			throw new Exception_Key($member);
		}
		$this->store_columns[$member] = $store;
	}

	/**
	 * @param array $members
	 * @return array
	 */
	private function _filterStoreMembers(array $members): array {
		foreach ($members as $member => $value) {
			if (!($this->store_columns[$member] ?? null)) {
				unset($members[$member]);
			}
		}
		return $members;
	}

	/**
	 * @param string|array $members
	 * @param bool $store
	 * @return $this
	 * @throws Exception_Key
	 */
	public function setMemberStore(string|array $members, bool $store = true): self {
		foreach (toList($members) as $member) {
			$this->_setStoreMember($member, $store);
		}
		return $this;
	}

	/**
	 * Fetch the store state for a member
	 *
	 * @param string $member
	 * @return bool
	 * @throws Exception_Key
	 */
	public function memberStore(string $member): bool {
		return $this->_storeMember($member);
	}

	/**
	 * Does this object member have a corresponding column in the database?
	 *
	 * @param string $member
	 * @return boolean
	 */
	public function hasColumn(string $member): bool {
		return array_key_exists($member, $this->class->column_types);
	}

	/**
	 * Does this object define the member given? (Does not determine if it has a value or not)
	 *
	 * Concept of member means a class column type defined.
	 *
	 * @param string $member
	 * @return boolean
	 * @see ORMBase::member_empty
	 */
	public function hasMember(string $member): bool {
		return array_key_exists($member, $this->class->column_types);
	}

	/**
	 * @return string[]
	 */
	protected function defaultDuplicateRenamePatterns(): array {
		$patterns = [];
		$patterns[] = '';
		$limit = min($this->option('duplicate_rename_limit', 100), 1000);
		for ($i = 1; $i < $limit; $i++) {
			$patterns[] = " $i";
		}
		return $patterns;
	}

	/**
	 * Rename a copy
	 *
	 * @param string $column
	 * @param Database_Query_Select $select
	 * @param ?string $rename_pattern
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	protected function duplicateRename(string $column, Database_Query_Select $select, string $rename_pattern = null): self {
		$name = $this->get($column);
		$class = get_class($this);
		if ($rename_pattern === null) {
			$locale = $this->application->locale;
			$rename_pattern = $this->option('duplicate_rename', $locale->__("$class:={0} (Copy{1})"));
		}
		// Quote all characters but {} which are used in the map call
		$rename_pattern = preg_quote($rename_pattern, '#');
		$rename_pattern = strtr($rename_pattern, ['\\{' => '{', '\\}' => '}', ]);
		$preg_pattern = '#^' . map($rename_pattern, [
			'(.*)', '([ 0-9]*)',
		]) . '$#';
		$matches = null;
		// If pattern found, pull out new base name (e.g. "Foo (Copy 2)" => "Foo"
		$base_name = preg_match($preg_pattern, $name, $matches) ? $matches[1] : $name;
		// Gather patterns to be used for new names (must include spacing if needed
		$patterns = $this->callHookArguments('duplicate_rename_patterns');
		if (!is_array($patterns)) {
			$patterns = $this->defaultDuplicateRenamePatterns();
		}
		foreach ($patterns as $pattern) {
			// Generate a new name
			$test_name = trim(map($rename_pattern, [$base_name, $pattern, ]));
			$select->addWhat('*X', "COUNT(DISTINCT $column)");
			$select->addWhere($column, $test_name);
			if ($select->integer('X') === 0) {
				// If it doesn't exist, then we're done
				$this->set($column, $test_name);
				return $this;
			}
		}

		throw new Exception_ORMDuplicate(get_class($this), 'Unable to recreate duplicate {class} using duplicateRename');
	}

	/**
	 * @param Interface_Duplicate|null $options Any subclass of options
	 * @return self
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_ORMEmpty
	 */
	public function duplicate(Interface_Duplicate $options = null): self {
		$member_names = ArrayTools::valuesRemove(array_keys($this->class->column_types), $this->class->primary_keys);
		$this->application->logger->debug('member_names={names}', ['names' => $member_names, ]);
		if ($options) {
			$new_object = $options->duplicate($this);
		} else {
			$new_object = $this->ormFactory(get_class($this), $this->members($member_names), $this->inheritOptions());
		}
		return $new_object;
	}

	/**
	 * Prepare the internal data structure for output to the database
	 *
	 * Calls
	 *
	 * $this->hook_insert_alter(array $data)
	 * ORM::insert_alter(ORM $object, array $data)
	 *
	 * Note final data structure will be trimmed down to values which exist in $this->store_columns
	 *
	 * @return array
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	protected function prepareInsert(): array {
		$members = $this->callHookArguments('pre_insert', [$this->members, ], $this->members);
		$members = $this->_filterStoreMembers($members);
		$this->selectDatabase();
		return $this->toDatabase($members, true);
	}

	/**
	 * @return void
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	private function insert(): void {
		if ($this->optionBool('disable_database') || $this->optionBool('disable_database_insert')) {
			return;
		}
		$members = $this->prepareInsert();
		if (count($members) === 0) {
			throw new Exception_ORMEmpty(get_class($this), '{class}: All members: {members} Store members: {store}', [
				'members' => array_keys($this->members), 'store' => $this->store_columns,
			]);
		}

		try {
			if ($this->class->auto_column) {
				$auto_id = $this->database()->insert($this->table(), $members);
				if ($auto_id > 0) {
					$this->setMember($this->class->auto_column, $auto_id);
					$this->callHook('insert', $auto_id);
					return;
				} else {
					throw new Exception_Store(get_class($this), 'Unable to insert (no id)');
				}
			}
			$result = $this->database()->insert($this->table(), $members, ['id' => false, ]);
			$this->callHook('insert', $result);
		} catch (Database_Exception_Duplicate $e) {
			$this->callHook('insert_failed', $e);

			throw new Exception_ORMDuplicate(get_class($this), $e->getMessage());
		}
	}

	/**
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	private function update(): void {
		if ($this->optionBool('disable_database') || $this->optionBool('disable_database_update')) {
			return;
		}
		$members = $this->_filterStoreMembers($this->members);
		$this->selectDatabase();
		$members = $this->toDatabase($members);
		$where = [];
		foreach ($this->class->primary_keys as $primary_key) {
			if (!array_key_exists($primary_key, $members)) {
				throw new Exception_Store(get_class($this), 'Can not update when {primary_key} not set (All primary keys: {primary_key_samples}) (Member keys: {members_keys})', [
					'primary_key' => $primary_key,
					'primary_key_samples' => JSON::encode($this->members($this->class->primary_keys)),
					'members_keys' => array_keys($members),
				]);
			} else {
				$where[$primary_key] = $members[$primary_key];
				unset($members[$primary_key]);
			}
		}
		if (count($where) === 0) {
			$locale = $this->application->locale;

			throw new Exception_Semantics($locale->__('Updating {class} without a where clause {primary_keys}', [
				'class' => get_class($this), 'primary_keys' => implode(', ', $this->class->primary_keys),
			]));
		}
		foreach ($members as $member => $value) {
			if (str_starts_with($member, '*')) {
				continue;
			}
			if (!array_key_exists($member, $this->original)) {
				continue;
			}
			if ($value === $this->original[$member]) {
				unset($members[$member]);
			}
		}
		$members = $this->callHook('update_alter', $members);
		if (count($members) === 0) {
			if (self::$debug) {
				$this->application->logger->debug('Update of {class}:{id} - no changes', [
					'class' => get_class($this), 'id' => $this->id(),
				]);
			}
			return;
		}

		try {
			$result = $this->database()->update($this->table(), $members, $where);
			$this->callHook('update', $members, $result);
			$this->original = $this->members + $this->original;
		} catch (Database_Exception_Table_NotFound|Database_Exception_SQL|Database_Exception_Duplicate $e) {
			$this->callHook('update_failed', $members, $e);
		}
	}

	/**
	 * Returns a new object which contains the found ORM, or null if not found
	 *
	 * @param string|array $where How to find this object (uses default ->exists where clause)
	 * @return self
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 */
	public function find(string|array $where = []): self {
		try {
			$data = $this->exists($where);
		} catch (Exception_Semantics|Exception_Key|Exception_Convert $e) {
			throw new Exception_ORMNotFound(self::class, 'Error during exists {exceptionClass} {message}', $e->variables(), $e);
		}

		try {
			return $this->initialize($data, true)->polymorphicChild()->setObjectStatus(self::STATUS_EXISTS);
		} catch (Exception_ORMEmpty $e) {
			throw $e;
		} catch (Throwable $e) {
			throw new Exception_ORMEmpty(self::class, 'Error during initialize {exceptionClass} {message}', zeskException::exceptionVariables($e), $e);
		}
	}

	/**
	 * @param string|array $where
	 * @return array
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	public function exists(string|array $where = ''): array {
		if (is_string($where) && !empty($where)) {
			if ($this->hasMember($where)) {
				$where = [$where => $this->member($where), ];
			}
		}
		if (!is_array($where) || count($where) === 0) {
			$find_keys = $this->class->find_keys;
			if (empty($find_keys)) {
				throw new Exception_ORMNotFound($this, 'No find keys for class {class}', ['class' => get_class($this)]);
			}
			$where = $this->class->duplicate_where;
			foreach ($find_keys as $k) {
				$where[$k] = $this->member($k);
			}
			$where = $this->toDatabase($where);
		}
		$this->selectDatabase();
		$query = $this->querySelect('X');
		$query->appendWhere($where);
		$query->setOrderBy($this->class->find_order_by);

		try {
			return $query->one();
		} catch (Database_Exception_SQL|Exception_Key) {
			throw new Exception_ORMNotFound($this);
		}
	}

	/**
	 * @return bool
	 */
	public function isDuplicate(): bool {
		$duplicate_keys = $this->class->duplicate_keys;
		if (!$duplicate_keys) {
			return false;
		}

		try {
			$members = $this->members($duplicate_keys);
			$query = $this->querySelect('X')->appendWhere($members)->addWhat('*n', 'COUNT(*)');
			if (!$this->isNew()) {
				$not_ids = $this->members($this->primaryKeys());
				$not_ids = ArrayTools::suffixKeys($not_ids, '|!=');
				$query->appendWhere($not_ids);
			}
			return toBool($query->integer('n'));
		} catch (Database_Exception_Duplicate) {
			return true;
		} catch (Exception_Class_NotFound|Exception_Key|Exception_ORMEmpty|Exception_ORMNotFound|Exception_Semantics|Database_Exception_Table_NotFound|Database_Exception_NoResults) {
			return false;
		}
	}

	/**
	 * @param mixed|null $value
	 * @param string $column
	 * @return ORMBase
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parse
	 */
	public function fetchByKey(mixed $value = null, string $column = ''): ORMBase {
		if (empty($column)) {
			$column = $this->findKey();
			if (empty($column)) {
				$column = $this->class->id_column;
			}
		}

		try {
			return $this->initialize($this->exists([$column => $value, ]), true)->polymorphicChild();
		} catch (Exception_Parameter|Exception_Semantics|Exception_Key $previous) {
			throw new Exception_ORMNotFound(get_class($this), 'fetchByKey({value}, {column})', [
				'value' => $value, 'column' => $column,
			], $previous);
		}
	}

	/**
	 * @return Database_Query_Select|string
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	protected function fetchQuery(): Database_Query_Select|string {
		$primary_keys = $this->class->primary_keys;
		if (count($primary_keys) === 0) {
			throw new Exception_Semantics('{get_class} {method} can not access fetch_query when there\'s no primary keys defined', [
				'get_class' => get_class($this), 'method' => __METHOD__,
			]);
		}
		$keys = $this->members($primary_keys);
		return $this->querySelect()->ormWhat()->appendWhere($keys)->setOffsetLimit(0, 1);
	}

	/**
	 * @param array $data
	 * @param bool $insert
	 * @return array
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	private function toDatabase(array $data, bool $insert = false): array {
		return $this->class->to_database($this, $data, $insert);
	}

	/**
	 * @return bool
	 */
	public function deleted(): bool {
		return $this->_deleted($this->members);
	}

	/**
	 * @param bool $set
	 * @return $this
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function setDeleted(bool $set): self {
		$col = $this->class->column_deleted;
		if ($col) {
			$this->__set($col, $set);
		}
		return $this;
	}

	/**
	 * Is this deleted?
	 *
	 * @param array $data
	 * @return bool
	 */
	private function _deleted(array $data): bool {
		$col = $this->class->column_deleted;
		if (!$col) {
			return false;
		}
		if (!array_key_exists($col, $data)) {
			return false;
		}
		return toBool($data[$this->class->column_deleted]);
	}

	/**
	 * Is this object polymorphic (multiple classes handling a single table)
	 *
	 * @return boolean
	 */
	public function polymorphic(): bool {
		return $this->class->polymorphic !== null;
	}

	/**
	 * Set the class used to instantiate rows from this object.
	 *
	 * @param string $set
	 *            Set polymorphic class - used internally from Class_Base
	 * @return $this boolean
	 */
	public function setPolymorphicLeaf(string $set): self {
		$this->polymorphic_leaf = $set;
		return $this;
	}

	/**
	 * Convert to true form.
	 * Override in subclasses to get custom polymorphic behavior.
	 *
	 * @return self
	 */
	protected function polymorphicChild(): self {
		if (!$this->polymorphic_leaf) {
			return $this;
		}
		if (is_a($this, $this->polymorphic_leaf)) {
			return $this;
		}

		try {
			return $this->ormFactory($this->polymorphic_leaf, $this->members, [
				'initialize' => 'internal',
				'class_object' => $this->class->polymorphic_inherit_class ? $this->class : null,
			] + $this->options);
		} catch (Exception_Class_NotFound $e) {
			$this->application->logger->error('Polymorphic conversion failed to class {polymorphic_leaf} from class {class}', [
				'polymorphic_leaf' => $this->polymorphic_leaf, 'class' => get_class($this),
			]);
			$this->application->hooks->call('exception', $e);
			return $this;
		}
	}

	/**
	 * Are the primary keys for this object non-null?
	 *
	 * @return bool
	 */
	private function canFetch(): bool {
		if (!$this->classValid) {
			$this->application->logger->error('canFetch {class} classValid=false', ['class' => get_class($this)]);
			return false;
		}
		foreach ($this->class->primary_keys as $pk) {
			if (!($this->members[$pk] ?? null)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param array $mixed
	 * @return $this
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function fetch(array $mixed = []): self {
		if (!$this->canFetch()) {
			throw new Exception_ORMEmpty(get_class($this), '{class}: Missing primary key {primary_keys} values: {values}', [
				'primary_keys' => $this->class->primary_keys, 'values' => $this->members($this->class->primary_keys),
			]);
		}
		$mixed = $this->callHook('fetch_enter', $mixed);
		if (count($mixed) !== 0) {
			$this->initialize($mixed)->polymorphicChild();
		}
		$hook_args = func_get_args();
		$this->need_load = false;
		$this->selectDatabase();
		$obj = $this->fetchObject();
		if (!$obj) {
			if (($result = $this->callHookArguments('fetch_not_found', $hook_args)) !== null) {
				return $result;
			}

			throw new Exception_ORMNotFound(get_class($this), 'Fetching {id}', $this->variables());
		}
		if ($this->_deleted($obj)) {
			if (($result = $this->callHookArguments('fetch_deleted', $hook_args)) !== null) {
				return $result;
			}

			$this->orm_not_found_exception(new Exception_ORMNotFound(get_class($this)), '-this-', $this->id());
		}
		$result = $this->initialize($obj, true)->polymorphicChild();
		return $result->callHookArguments('fetch', $hook_args, $result);
	}

	/**
	 * @return array
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	protected function fetchObject(): array {
		$sql = $this->fetchQuery();

		try {
			return $this->database()->queryOne(strval($sql));
		} catch (Database_Exception_NoResults|Database_Exception_Duplicate $e) {
			throw new Exception_ORMNotFound(self::class, $e->getMessage(), $e->variables(), $e);
		}
	}

	/**
	 * Retrieve errors during storing object
	 *
	 * @return array
	 */
	public function storeErrors(): array {
		return $this->optionArray(self::OPTION_STORE_ERROR);
	}

	/**
	 * Retrieve the error string for the error when a duplicate is found in the database when
	 * storing
	 *
	 * @return string
	 */
	private function error_duplicate(): string {
		return strval($this->option(self::OPTION_DUPLICATE_ERROR, '{indefinite_article} {name} with that name already exists. ({id})'));
	}

	protected function setMemberStoreError(string $member, string $message): self {
		$errors = $this->optionArray(self::OPTION_STORE_ERROR);
		$errors[$member] = $message;
		$this->setOption(self::OPTION_STORE_ERROR, $errors);
		return $this;
	}

	protected function store_queue(): void {
		foreach ($this->store_queue as $q) {
			$func = array_shift($q);
			call_user_func_array([$this, $func, ], $q);
		}
		$this->store_queue = [];
	}

	/**
	 * @return $this
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public function store(): self {
		/*
		 * Avoid infinite loops with objects linked back to themselves.
		 */
		if ($this->storing) {
			return $this;
		}


		$this->storing = true;
		/*
		 * Avoid storing identical items if possible
		 */
		/**
		 * When duplicating, we want to check is_duplicate only, so remove exists - not sure
		 */
		if ($this->isDuplicate()) {
			throw new Exception_ORMDuplicate(get_class($this), $this->error_duplicate(), [
				'duplicate_keys' => $this->class->duplicate_keys, 'name' => $this->className(), 'id' => $this->id(),
				'indefinite_article' => $this->application->locale->indefinite_article($this->class->name),
			]);
		}
		$this->storeMembers();
		$this->callHook('store');
		/*
		 * Insert/Update
		 */
		try {
			if ($this->hasPrimaryKeys()) {
				$this->update();
			} else {
				$this->insert();
			}
			$this->store_queue();
		} catch (Database_Exception_Duplicate $e) {
			throw new Exception_ORMDuplicate($this, $e->getMessage(), $e->variables(), $e);
		} catch (Database_Exception_Table_NotFound $e) {
			throw new Exception_ORMNotFound($this, $e->getMessage(), $e->variables(), $e);
		}

		$this->is_new_cached = null;
		$this->storing = false;
		$this->original = $this->toDatabase($this->members);
		$this->callHook('stored');
		return $this;
	}

	/**
	 *
	 * @param ORMBase $that
	 * @return boolean
	 * @throws Exception_ORMEmpty
	 */
	public function isEqual(ORMBase $that): bool {
		return $this::class === $that::class && $this->id() === $that->id();
	}

	/**
	 * Store any objects which are members, first
	 * @return void
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound - can be removed with preflight
	 * @throws Exception_Configuration - can be removed with preflight
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter - can be removed with preflight
	 * @throws Exception_Parse - can be removed with preflight
	 * @throws Exception_Semantics - can be removed with preflight
	 */
	private function storeMembers(): void {
		/*
		 * Store child objects
		 */
		foreach ($this->class->has_one as $member => $class) {
			if ($class[0] === '*') {
				$class = $this->member(substr($class, 1));
				if (!$class) {
					continue;
				}
			}
			$result = $this->raw_member($member);
			if ($result instanceof $class) {
				try {
					if (!$result->storing && ($result->isNew() || $result->changed()) && !$result->is_equal($this)) {
						$result->store();
					}
				} catch (Exception_ORMNotFound $e) {
					$this->orm_not_found_exception($e, $member, $result);
				}
			}
		}
	}

	/**
	 * @return void
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	protected function beforeRegister(): void {
		foreach ($this->class->has_one as $member => $class) {
			if ($class[0] === '*') {
				$class = $this->member(substr($class, 1));
			}
			if (!empty($class)) {
				$object = $this->member($member);
				/* @var $object ORMBase */
				if ($object instanceof $class) {
					$object->register();
				}
			}
		}
	}

	/**
	 * Register an object based on its "find_keys"
	 * Register means "create it if it doesn't exist, find it if it does"
	 *
	 * @param array|string $where
	 * @return $this
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 * @see ORMBase::statusExists
	 * @see ORMBase::status_insert
	 * @see ORMBase::status_unknown
	 */
	public function register(array|string $where = ''): self {
		// If we have all of our primary keys and has an auto_column, then don't bother registering.
		// Handles case when pre_register registers any objects within it. If everything is loaded OK, then we know
		// these are valid objects.
		if ($this->hasPrimaryKeys() && $this->class->auto_column && !$this->optionBool(self::OPTION_IGNORE_AUTO_COLUMN)) {
			return $this;
		}
		$this->beforeRegister();

		try {
			$data = $this->exists($where);
			$result = $this->initialize($data, true)->polymorphicChild();
			return $result->setObjectStatus(self::STATUS_EXISTS);
		} catch (Exception_ORMNotFound) {
		}
		return $this->store()->setObjectStatus(self::STATUS_INSERT);
	}

	/**
	 * Set/get result of object operation
	 *
	 * @param string $set
	 * @return self
	 */
	public function setObjectStatus(string $set): self {
		$this->status = $set;
		return $this;
	}

	/**
	 * Get result of object operation
	 *
	 * @return string
	 */
	public function objectStatus(): string {
		return $this->status;
	}

	/**
	 *
	 * @return boolean
	 */
	public function statusExists(): bool {
		return $this->status === self::STATUS_EXISTS;
	}

	/**
	 *
	 * @return boolean
	 */
	public function statusCreated(): bool {
		return $this->status === self::STATUS_INSERT;
	}

	/**
	 * Convert to string
	 */
	public function __toString(): string {
		if (!$this->classValid) {
			return '';
		}

		try {
			$id = $this->id();
		} catch (Throwable) {
			return '';
		}
		if (is_numeric($id)) {
			return strval($id);
		}
		if (is_array($id)) {
			$id = ArrayTools::flatten($id);
			ksort($id);
		}
		return PHP::dump($id);
	}

	/**
	 * Delete an object from the database
	 *
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMNotFound
	 */
	public function delete(): void {
		if ($this->isNew()) {
			return;
		}
		// 		$cache = $this->object_cache();
		// 		$cache->delete();

		if ($this->optionBool('disable_database')) {
			return;
		}
		$where = [];
		foreach ($this->class->primary_keys as $k) {
			$where[$k] = $this->member($k);
		}
		$this->selectDatabase();
		$result = $this->database()->delete($this->table, $where);
		if ($this->database()->affectedRows($result) === 0) {
			$this->callHook('delete_already');
			return;
		}
		$this->callHook('delete');
	}

	/**
	 * Given a class $class, determine the default path to another class
	 *
	 * @param $class string
	 * @return string
	 * @throws Exception_ORMNotFound
	 */
	public function link_default_path_to(string $class): string {
		return $this->class->link_default_path_to($class);
	}

	/**
	 * Walk path to $class while updating the query
	 *
	 * @param Database_Query_Select $query
	 * @param array $link_state
	 *            An array of link settings, or a string indicating the path to link to
	 *            The settings in the array are:
	 *            <code>
	 *            "path" => "ORM_Member.NextORM_Member.Column"
	 *            </code>
	 * @return Database_Query_Select
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function linkWalk(Database_Query_Select $query, array $link_state = []): Database_Query_Select {
		return $this->class->linkWalk($this, $query, $link_state);
	}

	/**
	 * Traverse an ORM using various settings.
	 *
	 * @param Walker $options
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function walk(Walker $options): array {
		return $options->walk($this);
	}

	/**
	 * Traverse an ORM using various settings for generation of JSON.
	 *
	 * @param JSONWalker $options
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function json(JSONWalker $options): array {
		return $options->walk($this);
	}

	/**
	 * Utility function for retrieving permissions.
	 *
	 * Add static function permissions() to your subclass and call this to get useful permissions
	 *
	 * @param Application $application
	 * @param string $class
	 * @return array
	 */
	public static function default_permissions(Application $application, string $class): array {
		$object = $application->ormRegistry($class);
		$name = $object->class->name;
		$locale = $application->locale;
		$names = $locale->plural($name);
		$__ = ['object' => $name, 'objects' => $names, ];
		$prefix = $class . '::';
		return [
			$prefix . 'view' => [
				'title' => $locale->__('View {object}', $__), 'class' => $class,
				'before_hook' => ['allowed_if_all' => ["$class::view all", ], ],
			], $prefix . 'view all' => ['title' => $locale->__('View all {objects}', $__), ], $prefix . 'edit' => [
				'title' => $locale->__('Edit {object}', $__), 'class' => $class,
				'before_hook' => ['allowed_if_all' => ["$class::edit all", ], ],
			], $prefix . 'edit all' => ['title' => $locale->__('Edit all {objects}', $__), ],
			$prefix . 'new' => ['title' => $locale->__('Create {objects}', $__), ],
			$prefix . 'delete all' => ['title' => $locale->__('Delete any {objects}', $__), ], $prefix . 'delete' => [
				'title' => $locale->__('Delete {object}', $__),
				'before_hook' => ['allowed_if_all' => ["$class::delete all", ], ], 'class' => $class,
			], $prefix . 'list' => ['title' => $locale->__('List {objects}', $__), ],
		];
	}

	/**
	 *
	 * @return string
	 * @see Debug::_dump
	 */
	public function _debug_dump(): string {
		try {
			$rows['primary_keys'] = $this->primaryKeys();
		} catch (Throwable $e) {
			$rows['primary_keys'] = null;
			$rows['idExceptionMessage'] = $e->getMessage();
			$rows['idExceptionClass'] = $e::class;
		}
		$rows['class'] = get_class($this->class);
		$rows['database'] = $this->database()->codeName();
		$rows['members'] = $this->members;
		return get_class($this) . " {\n" . Text::indent(Text::format_pairs($rows)) . "\n}\n";
	}

	/**
	 * Was deprecated 2012 - why? Where will this go?
	 *
	 * Replaced by ->variables()
	 *
	 * @return array string
	 */
	public function words(): array {
		$locale = $this->application->locale;
		$name = $this->class->name;
		$namePlural = $locale->plural($name);
		$localeName = $locale->__($name);
		$localeNameLower = strtolower($localeName);
		$localeNameTitleCase = StringTools::capitalize($localeNameLower);
		$localeNameLowerPlural = $locale->plural($localeNameLower);
		$localeNameCapitalized = ucfirst($localeNameLower);
		$localeNameCapitalizedPlural = ucfirst($localeNameLowerPlural);

		$spec['class_name-raw'] = $name;
		$spec['class_name'] = $localeName;
		$spec['class_name-singular'] = $localeName;
		$spec['class_name-context-object'] = $localeNameLower;
		$spec['class_name-context-object-singular'] = $localeNameLower;
		$spec['class_name-context-object-plural'] = $localeNameLowerPlural;
		$spec['class_name-context-subject'] = $localeNameCapitalized;
		$spec['class_name-context-subject-singular'] = $localeNameCapitalized;
		$spec['class_name-context-subject-plural'] = $localeNameCapitalizedPlural;
		$spec['class_name-context-title'] = $localeNameTitleCase;
		$spec['class_name-context-subject-indefinite-article'] = $locale->indefinite_article($name, true);
		$spec['class_name-plural'] = $namePlural;

		$spec['display_name'] = $this->displayName();

		return $spec;
	}

	/**
	 * How to retrieve this object when passed as an argument to a router
	 *
	 * @param Route $route
	 * @param string $arg
	 * @return self
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function hook_routerArgument(Route $route, string $arg): self {
		$route->setOption('routerArgument', $arg);
		return $this->setId($arg)->fetch();
	}

	/**
	 * Name/value pairs used to generate the schema for this object
	 *
	 * @return array
	 */
	public function schema_map(): array {
		return $this->optionArray('schema_map') + ['table' => $this->table(), ];
	}

	/**
	 * @param mixed $mixed
	 * @return string
	 * @throws Exception_Parameter
	 */
	public static function mixedToClass(mixed $mixed): string {
		if ($mixed instanceof ORMBase) {
			return $mixed::class;
		}
		if ($mixed instanceof Class_Base) {
			return $mixed->class;
		}
		if (is_string($mixed)) {
			return $mixed;
		}

		throw new Exception_Parameter('mixedToClass takes ORM ORMClass or String {type} given', ['type' => gettype($mixed)]);
	}
}
