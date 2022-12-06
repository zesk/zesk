<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Cache\CacheItemInterface;
use zesk\ORM\Walker;
use zesk\ORM\JSONWalker;

/**
 * Object Relational Mapping base class. Extend this class and Class_ORM to create an ORM object.
 *
 * @todo Remove dependencies on $this->class->has_many and $this->class->has_one access
 * @author kent
 * @see Module_ORM
 * @see Class_ORM
 */
class ORM extends Model implements Interface_Member_Model_Factory {
	/**
	 * Boolean value which affects ORM::is_new() and ORM::register() which will not depend
	 * on the auto_column's presence to determine if an ORM is new or not.
	 * Will actually check
	 * the database. Allows you to have objects which normally would be created via auto-increment
	 * but instead allows you to create them specifically by ID. Usually used temporarily.
	 *
	 * Do not set this on a global basis via global ORM::ignore_auto_column=true as it will
	 * likely have catastrophic negative results on performence.
	 *
	 * @var string
	 */
	public const option_ignore_auto_column = 'ignore_auto_column';

	/**
	 * Previous call resulted in a new object retrieved from the database which exists
	 *
	 * @see ORM::register
	 * @see ORM::fetch_if_exists
	 * @var string
	 */
	public const STATUS_EXISTS = 'exists';

	/**
	 * Previous call resulted in the saving of the existing object in the database
	 *
	 * @see ORM::register
	 * @see ORM::fetch_if_exists
	 * @var string
	 */
	public const STATUS_INSERT = 'insert';

	/**
	 * Previous call failed or has an unknown result
	 *
	 * @see ORM::register
	 * @see ORM::fetch_if_exists
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
	 * @var Class_ORM
	 */
	protected Class_ORM $class;

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
	private ?Database $database = null;

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
	 * Cache stack
	 *
	 * @var array
	 */
	private array $cache_stack = [];

	/**
	 * Retrieve user-configurable settings for this object
	 *
	 * @return array
	 */
	public static function settings() {
		return []; //TODO
	}

	/**
	 * Syntactic sugar - returns ORM not a Model
	 *
	 * @param string $class
	 * @param mixed $mxied
	 * @param array $options
	 * @return self
	 */
	public function ormFactory(string $class, mixed $mixed = null, array $options = []): self {
		$object = $this->modelFactory($class, $mixed, $options);
		assert($object instanceof ORM);
		return $object;
	}

	/**
	 * Create a new object
	 *
	 * @param mixed $mixed
	 *            Initializing value; either an id or an array of member names => values.
	 * @param array $options
	 *            List of Options to set before initialization
	 */
	public function __construct(Application $application, $mixed = null, array $options = []) {
		parent::__construct($application, null, $options);
		$this->inheritConfiguration();
		$this->initializeSpecification();
		$this->members = $this->class->column_defaults;
		$this->initialize($mixed, $this->option('initialize'));
		$this->setOption('initialize', null);
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
		$this->initializeSpecification();
		$this->initialize($this->members, 'raw');
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
	 * Retrieve the Class_ORM associated with this object.
	 * Often matches "Class_" . get_class($this), but not always.
	 *
	 * @return Class_ORM
	 */
	public function class_orm(): Class_ORM {
		return $this->class;
	}

	/**
	 * Retrieve the Class_ORM associated with this object.
	 * Often matches "Class_" . get_class($this), but not always.
	 *
	 * @return Class_ORM
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
		return $this->members() + ArrayTools::prefixKeys($this->class->variables(), '_class_') + [
			'ORM::class' => get_class($this), __CLASS__ . '::class' => get_class($this),
		];
	}

	/**
	 * @param string $mixed
	 * @param mixed|null $default
	 * @return mixed
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
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
	 * @return Model $this
	 */
	public function set(string $mixed, mixed $value = null): self {
		$this->__set($mixed, $value);
		return $this;
	}

	/**
	 * Retrieve a list of class dependencies for this object
	 */
	public function dependencies() {
		return $this->class->dependencies($this);
	}

	/**
	 * Initialize per-object settings
	 * @return void
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
		$this->class = Class_ORM::instance($this, [], $this->class_name);
		if (!$this->table) {
			$this->table = $this->class->table;
		}
		if (!$this->database_name) {
			$this->database_name = $this->class->database_name;
		}
		$this->store_columns = ArrayTools::keysFromValues(array_keys($this->class->column_types), true);
		$this->store_queue = [];
		$this->original = [];
	}

	/**
	 * Clean a code name to be without spaces or special characters. Numbers, digits, and - and _ are ok.
	 *
	 * @param string $name
	 * @see self::clean_code_name
	 */
	public static function clean_code_name($name, $blank = '-') {
		$codename = preg_replace('|[\s/]+|', '-', strtolower(trim($name, " \t\n$blank")));
		$codename = preg_replace('/[^-_A-Za-z0-9]/', '', $codename);
		if ($blank !== '-') {
			$codename = strtr($codename, '-', $blank);
		}
		return $codename;
	}

	/**
	 *
	 * @param string $cache_id
	 * @return ORM_CacheItem
	 */
	public function cache_item($cache_id = null) {
		$name[] = get_class($this);
		$name[] = JSON::encode($this->id());
		if ($cache_id !== null) {
			$name[] = $cache_id;
		}
		/* @var $cache \Psr\Cache\CacheItemInterface */
		$cache = $this->application->cache->getItem(implode('/', $name));
		return new ORM_CacheItem($this->application, $cache);
	}

	/**
	 * Retrieve a cache attached to this object only
	 *
	 * @param $cache_id string
	 *            A specific cache for this object, or NULL for the global cache fo this object
	 * @return ORM_CacheItem|CacheItemInterface
	 */
	public function object_cache($cache_id = null) {
		$name[] = get_class($this);
		$name[] = JSON::encode($this->id());
		if ($cache_id !== null) {
			$name[] = $cache_id;
		}
		/* @var $cache \Psr\Cache\CacheItemInterface */
		$cache = $this->application->cache->getItem(implode('/', $name));
		$item = new ORM_CacheItem($this->application, $cache);
		$item->depends($this);
		return $item;
	}

	/**
	 *
	 * @return ?ORM_Schema
	 */
	final public function database_schema(): ?ORM_Schema {
		return $this->class->database_schema($this);
	}

	/**
	 *
	 */
	public function schema(): ORM_Schema|array|string|null {
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
	public function schema_changed(): void {
		if ($this->class->dynamic_columns) {
			$this->class->init_columns();
		}
	}

	/**
	 * Cache object data
	 */
	public function cache(string $key = null, string $data = null) {
		$this->application->deprecated();
		if ($key === null) {
			return $this->cacheList();
		} elseif ($data === null) {
			return $this->cacheLoad($key);
		} else {
			return $this->cacheSave($key, $data);
		}
	}

	/**
	 * @return array
	 */
	public function cacheList(): array {
		return toArray($this->callHook('cachList'), []);
	}

	public function cacheSave(string $key, string $data) {
		$this->callHook('cacheSave', $key, $data);
		return $this;
	}

	public function cacheLoad(string $key): ?string {
		return $this->callHookArguments('cache_load', [$key, ], null);
	}

	/**
	 * Cache object data
	 */
	public function cache_dirty($key = null): void {
		$this->callHook('cache_dirty', $key);
	}

	/**
	 * Cache output start, returns "false" if cache hit so do not generate output, e.g.
	 *
	 * if ($object->cache_output_begin("profile")) {
	 * // Generate profile using $object
	 * $object->cache_output_end();
	 * }
	 *
	 * @param mixed $key
	 * @return boolean
	 */
	public function cache_output_begin($key = null): bool {
		$data = $this->cacheLoad($key);
		if ($data) {
			echo $data;
			return false;
		}
		ob_start();
		$this->cache_stack[] = $key;
		return true;
	}

	/**
	 * End caching, save output to cache
	 *
	 * @return void
	 * @throws Exception_Semantics
	 */
	public function cache_output_end(): void {
		if (count($this->cache_stack) === 0) {
			throw new Exception_Semantics(get_class($this) . '::cache_output_end before cache_output_begin');
		}
		$content = ob_get_flush();
		$key = array_pop($this->cache_stack);
		$this->cacheSave($key, $content);
	}

	/**
	 *
	 * @return Database
	 * @throws Exception_Configuration|Exception_NotFound
	 */
	public function database(): Database {
		if ($this->database instanceof Database) {
			return $this->database;
		}
		return $this->database = $this->application->databaseRegistry($this->database_name);
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
		return $this->_get($name_col);
	}

	/**
	 * Retrieve the name column for this object (if any)
	 *
	 * @return string|null
	 */
	final public function nameColumn() {
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
	final public function findKeys() {
		return $this->class->find_keys;
	}

	/**
	 * Retrieve list of member names used to find a duplicate object in the database
	 *
	 * @return array:string
	 */
	final public function duplicateKeys() {
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
			} catch (Exception_Key) {
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
	 * List of primary keys for this object
	 *
	 * @return array:string
	 * @deprecated 2022-05
	 */
	public function primary_keys(): array {
		$this->application->deprecated(__METHOD__);
		return $this->primaryKeys();
	}

	/**
	 * Class code name
	 *
	 * @return string
	 * @deprecated 2022-05
	 */
	public function class_code_name(): string {
		$this->application->deprecated(__METHOD__);
		return $this->class->code_name;
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
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 */
	public function selectDatabase(): Database {
		return $this->database()->selectDatabase($this->databaseName());
	}

	/**
	 * Ensure this object is loaded from database if needed
	 * @return self
	 */
	public function refresh(): self {
		if ($this->need_load && $this->canFetch()) {
			$this->fetch();
		}
		$this->need_load = false;
		return $this;
	}

	/**
	 * ORM initialization; when creating an object this should be called using two methods: An
	 * integer ID for this object, or an array of populated values, or from the database itself
	 *
	 * @param array $mixed
	 * @param mixed $initialize
	 * @return void
	 */
	private function initializeMembers(array $mixed, mixed $initialize = false): void {
		$this->_inited = count($mixed) !== 0;
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
		$this->_inited = false;
		$this->members = $this->class->column_defaults;
		$this->original = [];
		$this->need_load = true;
	}

	/**
	 * @param mixed $id
	 * @return void
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	private function initializeId(mixed $id): void {
		if ($this->class->id_column !== null) {
			$this->setId($id);
			$this->_inited = true;
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
	 * @throws Exception_Parameter
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
	 * @param bool $isNew
	 * @return $this
	 */
	public function setIsNew(bool $isNew): self {
		$this->is_new_cached = $isNew;
		return $this;
	}

	/**
	 * @return bool
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	public function isNew(): bool {
		if (is_bool($this->is_new_cached)) {
			return $this->is_new_cached;
		}
		$auto_column = $this->class->auto_column;
		if ($auto_column && !$this->optionBool(self::option_ignore_auto_column)) {
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
		return true; // Always new
	}

	/**
	 * Empty out this object's members and set to defaults
	 *
	 * @return ORM
	 */
	public function clear(): self {
		$this->members = $this->class->column_defaults;
		$this->store_queue = [];
		return $this;
	}

	/**
	 * Ouptut the display name for this object.
	 *
	 * @return string
	 */
	public function displayName(): string {
		$name_column = $this->class->name_column;
		if (!$name_column) {
			return '';
		}
		return strval($this->member($name_column));
	}

	/**
	 * Get/set the ID for this object
	 *
	 * @param mixed $set
	 * @return $this
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function setId(mixed $set): self {
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
				$this->application->logger->warning('{class}::id("{set}") mismatches primary keys (expected {npk})', [
					'class' => get_class($this), 'set' => $set, 'npk' => count($pk),
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
	 */
	public function id(): int|string|array {
		$id_column = $this->class->id_column;
		/**
		 * Single ID
		 */
		if (is_string($id_column)) {
			// TODO Move this into member classes
			$id = $this->members[$id_column] ?? null;
			if ($id instanceof ORM) {
				return $id->id();
			}
			assert(array_key_exists($id_column, $this->class->column_types));
			$type = $this->class->column_types[$id_column];
			return $type === Class_ORM::type_id || $type === Class_ORM::type_integer ? intval($id) : strval($id);
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
		return $this->members($pk);
	}

	/**
	 * Returns name of the database used by this object
	 *
	 * @return string
	 * @see ORM::databaseName()
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
	 * @return Database_Query_Update
	 */
	public function queryUpdate(string $alias = ''): Database_Query_Update {
		$query = new Database_Query_Update($this->database());
		$query->setORMClassOptions($this->inheritOptions());
		return $query->setORMClass(get_class($this))->setTable($this->table(), $alias)->setValidColumns($this->columns(), $alias);
	}

	/**
	 * Create an delete query for this object
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
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	protected function memberIterator(string $member, array $where = []): ORMIterator {
		$has_many = $this->class->hasMany($this, $member);
		if (!$this->hasPrimaryKeys()) {
			throw new Exception_Semantics('Can not iterate on an uninitialized object {class}', [
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
	 * @param ORM|null $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Select
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function memberQuery(string $member, ORM &$object = null): Database_Query_Select {
		return $this->class->memberQuery($this, $member, $object);
	}

	/**
	 * Create a query for an object's member
	 *
	 * @param $member string
	 *            Many member
	 * @param ORM|null $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Update
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @todo Unimplemented
	 */
	public function memberQueryUpdate(string $member, ORM &$object = null): Database_Query_Update {
		return $this->class->memberQueryUpdate($this, $member, $object);
	}

	/**
	 *
	 * @param string $member
	 */
	private function memberForeignList(string $member): array {
		if ($this->isNew()) {
			return array_keys(toArray($this->members[$member] ?? []));
		}
		return $this->class->memberForeignList($this, $member);
	}

	private function memberForeignExists(string $member, int|string|array $id) {
		if ($this->isNew()) {
			return apath($this->members, [$member, $id, ]) !== null;
		}
		return $this->class->memberForeignExists($this, $member, $id);
	}

	private function memberForeignDelete(string $member): void {
		$queue = $this->class->memberForeignDelete($this, $member);
		if (is_array($queue)) {
			$this->store_queue += $queue;
		}
		//		if ($this->is_new()) {
		$this->members[$member] = [];
		//		}
	}

	private function memberForeignAdd($member, ORM $object): void {
		$foreign_keys = $object->members($object->primaryKeys());
		$hash = json_encode($foreign_keys);
		$this->members[$member][$hash] = $object;
		$this->store_queue += $this->class->memberForeignAdd($this, $member, $object);
	}

	private function _fk_delete($table, $foreign_key): void {
		$sql = $this->sql()->delete($table, [$foreign_key => $this->id()]);
		$this->database()->query($sql);
	}

	private function _fk_store(ORM $object, string $update_key): void {
		$object->set($update_key, $this->id())->store();
	}

	private function _fk_link_store(ORM $object, string $table, array $replace): void {
		if ($object->is_new() || $object->changed()) {
			$object->store();
		}
		$map = ['Foreign' => $this->id(), 'Far' => $object->id(), ];
		$this->database()->replace($table, map($replace, $map));
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
	 * @param string $data
	 *            Current data stored in member
	 * @param array $options
	 *            Options to create when creating object
	 * @return ORM
	 */
	public function memberModelFactory(string $member, string $class, mixed $mixed = null, array $options = []): ORM {
		return $this->ormFactory($class, $mixed, $options); //->refresh();
	}

	/**
	 *
	 * @param string|null $member
	 * @param mixed|null $data
	 * @return void
	 * @return void
	 * @throws Exception_ORM_NotFound
	 */
	private function orm_not_found_exception(Exception_ORM_NotFound $e, string $member = null, mixed $data = null): void {
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
	 * @return ORM|null
	 * @throws Exception_Key
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Semantics
	 */
	final protected function memberObject(string $member, array $options = []): ?ORM {
		$this->refresh();

		if (!array_key_exists($member, $this->members)) {
			throw new Exception_Key($member);
		}
		$data = $this->members[$member];
		if ($data === null) {
			return null;
		}
		if (!array_key_exists($member, $this->class->has_one)) {
			throw new Exception_Semantics('Accessing {class}::member_object but {member} is not in has_one', [
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
		} catch (Exception_ORM_NotFound $e) {
			$this->orm_not_found_exception($e, $member, $data);
		}

		throw new Exception_Key($member);
	}

	/**
	 * Does this object have a member value?
	 *
	 * {@inheritdoc}
	 *
	 * @see Model::has()
	 */
	public function has(string $member): bool {
		// Need to check $this->members to handle listing an object with additional query fields which may not be configured in the base object
		// Prevents ->defaults() from nulling the value if it's in there
		return $this->hasMember($member) || array_key_exists($member, $this->members) || isset($this->class->has_many[$member]);
	}

	/**
	 * Get member using getter, has_many, has_one, or a regular typed member. Internal only
	 *
	 * @param string $member
	 * @return mixed
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_ORM_Store
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function _get(string $member): mixed {
		if (($method = ($this->class->getters[$member] ?? null)) !== null) {
			if (!method_exists($this, $method)) {
				throw new Exception_Semantics("ORM getter \"$method\" for " . get_class($this) . ' does not exist');
			}
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
	 */
	public function __unset(string $key): void {
		if (array_key_exists($key, $this->class->has_many)) {
			$this->memberForeignDelete($key);
			$this->members[$key] = [];
			return;
		}

		try {
			$this->memberRemove($key);
		} catch (Exception_Key) {
		}
	}

	/**
	 * Lookup the current ORM using find_keys and the value supplied here.
	 *
	 * Returns a new ORM with loaded values
	 *
	 * @param int|string|array $value
	 * @return $this
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Parameter
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
		$result = $this->duplicate()->setMembers($value)->find();
		if (!$result) {
			throw new Exception_ORM_NotFound(get_class($this), 'Can not find {value} in {class}', [
				'value' => $value,
			]);
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function __isset(string $key): bool {
		if (array_key_exists($key, $this->class->has_many)) {
			return true;
		}
		return isset($this->members[$key]);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Parameter
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
			if (!$value instanceof ORM) {
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
			if ($value instanceof ORM) {
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
				} catch (Exception_ORM_NotFound) {
					return;
				}
			}
		}
		$this->setMember($key, $value);
		$this->_inited = true;
	}

	/**
	 * @param string $member
	 * @return array
	 * @throws Exception_Key
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
	 * @throws Exception_Parameter
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
	 * @throws Exception_Key
	 * @throws Exception_Convert
	 */
	public function memberInteger(string $member): int {
		$this->refresh();
		$value = $this->member($member);
		if (is_numeric($value)) {
			return intval($value);
		}
		if ($value instanceof ORM && $value->idColumn()) {
			$id = $value->id();
			assert(is_numeric($id));
			return intval($id);
		}

		throw new Exception_Convert('Unable to convert {value} of {type} to integer', [
			'value' => $value, 'type' => type($value),
		]);
	}

	/**
	 * Retrieve a member of this object. Note that you can set member values outside of an ORMs definition
	 * as many database operations allow this; but the value is required to be populated as
	 * a member prior to retrieval
	 *
	 * @param string $member Field to retrieve
	 * @return mixed
	 * @throws Exception_Key
	 */
	public function member(string $member): mixed {
		$this->refresh();
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
	 */
	public function membersChanged(string|array $members): bool {
		$members = toList($members);
		$data = $this->members($members);
		$column_types = $this->class->column_types;
		foreach ($members as $column) {
			if (array_key_exists($column, $column_types)) {
				$this->class->memberToDatabase($this, $column, $column_types[$column], $data);
			}
			if (($this->original [$column] ?? null) !== ($data [$column] ?? null)) {
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
	 */
	public function changed(array|string $members = ''): bool {
		return $this->membersChanged($members === '' ? $this->columns() : $members);
	}

	/**
	 * Retrieve the changes to this object as an array of member => array("old value", "new value")
	 *
	 * @return array
	 */
	public function changes(): array {
		$changes = [];
		foreach ($this->columns() as $k) {
			if ($this->membersChanged($k)) {
				$changes[$k] = [$this->original[$k] ?? null, $this->members[$k] ?? null];
			}
		}
		return $changes;
	}

	/**
	 * @param string $member
	 * @param mixed|null $def
	 * @return mixed
	 * @throws Exception_Key
	 */
	public function membere(string $member, mixed $def = null): mixed {
		$value = $this->member($member);
		if (empty($value)) {
			return $def;
		}
		return $value;
	}

	/**
	 * Passing in NULL for $mixed will fetch ALL members, including those which may be "extra" as returned by a custom query, for example.
	 *
	 * @param array|string|null $mixed
	 * @return array
	 */
	public function members(array|string $mixed = null): array {
		$this->refresh();
		if (is_string($mixed)) {
			$mixed = explode(';', $mixed);
		}
		if (!is_array($mixed)) {
			$mixed = array_keys($this->class->column_types);
			$result = $this->members; // Start with all members, overwrite ones which have getters/setters here
		} else {
			$result = [];
		}
		foreach ($mixed as $member) {
			try {
				$result[$member] = $this->_get($member);
			} catch (Exception_ORM_NotFound $e) {
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
		$this->refresh();
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
		$this->refresh();
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
	 * @param string $member
	 * @return mixed
	 * @throws Exception_Key
	 */
	public function memberRemove(string $member): mixed {
		$data = $this->member($member);
		unset($this->members[$member]);
		return $data;
	}

	/**
	 * Change the status of the store column structure
	 *
	 * @param string $member
	 * @param bool
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
	 * @see ORM::member_empty
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
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_ORM_Duplicate
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
		$patterns = $this->callHookArguments('duplicate_rename_patterns', [], null);
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

		throw new Exception_ORM_Duplicate(get_class($this), 'Unable to recreate duplicate {class} using duplicateRename');
	}

	/**
	 * @param Interface_Duplicate|null $options Any subclass of options
	 * @return self
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function duplicate(Interface_Duplicate &$options = null): self {
		$member_names = ArrayTools::valuesRemove(array_keys($this->class->column_types), $this->class->primary_keys);
		$this->application->logger->debug('member_names={names}', ['names' => $member_names, ]);
		$new_object_options = array_merge($this->inheritOptions(), $options ? $options->options() : []);
		$new_object = $this->ormFactory(get_class($this), $this->members($member_names), $new_object_options);
		$options?->processDuplicate($new_object);
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
	 * Note final data structure will be trimed down to values which exist in $this->store_columns
	 *
	 * @return array
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 */
	protected function prepareInsert(): array {
		$members = $this->callHookArguments('pre_insert', [$this->members, ], $this->members);
		$members = $this->_filterStoreMembers($members);
		$this->selectDatabase();
		return $this->toDatabase($members, true);
	}

	/**
	 * @return int 0 if noop, < 0 if no ID
	 * @throws Database_Exception
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_Store
	 */
	private function insert(): int {
		if ($this->optionBool('disable_database') || $this->optionBool('disable_database_insert')) {
			return 0;
		}
		$members = $this->prepareInsert();
		if (count($members) === 0) {
			throw new Exception_ORM_Empty(get_class($this), '{class}: All members: {members} Store members: {store}', [
				'members' => array_keys($this->members), 'store' => $this->store_columns,
			]);
		}

		try {
			if ($this->class->auto_column) {
				$auto_id = $this->database()->insert($this->table(), $members);
				if ($auto_id > 0) {
					$this->setMember($this->class->auto_column, $auto_id);
					$this->callHook('insert');
					return $auto_id;
				}

				throw new Exception_ORM_Store(get_class($this), 'Unable to insert (no id)');
			}
			$result = $this->database()->insert($this->table(), $members, ['id' => false, ]);
			$this->callHook('insert');
			return $result;
		} catch (Database_Exception_Duplicate $e) {
			$this->callHook('insert_failed', $e);

			throw new Exception_ORM_Duplicate(get_class($this), $e->getMessage());
		}
	}

	/**
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_Store
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
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
				throw new Exception_ORM_Store(get_class($this), 'Can not update when {primary_key} not set (All primary keys: {primary_key_samples}) (Member keys: {members_keys})', [
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
			return;
		} catch (\Exception $e) {
			$this->callHook('update_failed');

			throw $e;
		}
	}

	/**
	 * Returns a new object which contains the found ORM, or null if not found
	 *
	 * @param array $where How to find this object (uses default ->exists where clause)
	 * @return self
	 * @throws Exception_Key
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function find(array $where = []): ORM {
		$data = $this->exists($where);
		return $this->initialize($data, true)->polymorphicChild();
	}

	/**
	 * @param string|array $where
	 * @return $this
	 * @throws Exception_Key
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function fetch_if_exists(string|array $where = ''): self {
		try {
			return $this->setObjectStatus(self::STATUS_EXISTS)->initialize($this->exists($where), true);
		} catch (\Exception $e) {
			$this->setObjectStatus(self::STATUS_UNKNOWN);

			throw $e;
		}
	}

	/**
	 * @param string|array $where
	 * @return array
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_NotFound
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
				throw new Exception_ORM_NotFound($this, 'No find keys for class {class}', ['class' => get_class($this)]);
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
			throw new Exception_ORM_NotFound($this);
		}
	}

	/**
	 * @return bool
	 * @throws Database_Exception_SQL
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	public function isDuplicate(): bool {
		$duplicate_keys = $this->class->duplicate_keys;
		if (!$duplicate_keys) {
			return false;
		}
		$members = $this->members($duplicate_keys);
		$query = $this->querySelect('X')->appendWhere($members)->addWhat('*n', 'COUNT(*)');
		if (!$this->isNew()) {
			$not_ids = $this->members($this->primary_keys());
			$not_ids = ArrayTools::suffixKeys($not_ids, '|!=');
			$query->appendWhere($not_ids);
		}
		return toBool($query->integer('n'));
	}

	/**
	 * @param mixed|null $value
	 * @param string $column
	 * @return ORM
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_NotFound
	 */
	public function fetchByKey(mixed $value = null, string $column = ''): ORM {
		if (empty($column)) {
			$column = $this->findKey();
			if (empty($column)) {
				$column = $this->class->id_column;
			}
		}

		try {
			return $this->initialize($this->exists([$column => $value, ]), true)->polymorphicChild();
		} catch (Exception_Parameter|Database_Exception_SQL|Exception_Semantics|Exception_Key $previous) {
			throw new Exception_ORM_NotFound(get_class($this), 'fetchByKey({value}, {column})', [
				'value' => $value, 'column' => $column,
			]);
		}
	}

	/**
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function fetchQuery(): string {
		$primary_keys = $this->class->primary_keys;
		if (count($primary_keys) === 0) {
			throw new Exception_Semantics('{get_class} {method} can not access fetch_query when there\'s no primary keys defined', [
				'get_class' => get_class($this), 'method' => __METHOD__,
			]);
		}
		$keys = $this->members($primary_keys);
		$sql = $this->sql()->select(['what' => '*', 'tables' => $this->table(), 'where' => $keys, 'limit' => 1, ]);
		return $sql;
	}

	/**
	 * @param array $data
	 * @param bool $insert
	 * @return array
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
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Parameter
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
	 *            Set polymorphic class - used internally from Class_ORM
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
		$class = get_class($this);
		if (!$this->polymorphic_leaf) {
			return $this;
		}
		if (is_a($this, $this->polymorphic_leaf)) {
			return $this;
		}

		try {
			$result = $this->ormFactory($this->polymorphic_leaf, $this->members, [
				'initialize' => 'internal',
				'class_object' => $this->class->polymorphic_inherit_class ? $this->class : null,
			] + $this->options);
			return $result;
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
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	public function fetch(array $mixed = []): self {
		if (!$this->canFetch()) {
			throw new Exception_ORM_Empty(get_class($this), '{class}: Missing primary key {primary_keys} values: {values}', [
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
			if (($result = $this->callHookArguments('fetch_not_found', $hook_args, null)) !== null) {
				return $result;
			}

			throw new Exception_ORM_NotFound(get_class($this), 'Fetching {id}', $this->variables());
		}
		if ($this->_deleted($obj)) {
			if (($result = $this->callHookArguments('fetch_deleted', $hook_args, null)) !== null) {
				return $result;
			}

			$this->orm_not_found_exception(new Exception_ORM_NotFound(get_class($this)), '-this-', $this->id());
		}
		$result = $this->initialize($obj, true)->polymorphicChild();
		return $result->callHookArguments('fetch', $hook_args, $result);
	}

	/**
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function fetchObject(): array {
		$sql = $this->fetchQuery();
		return $this->database()->queryOne($sql);
	}

	/**
	 * Retrieve errors during storing object
	 *
	 * @return array
	 */
	public function storeErrors(): array {
		return $this->optionArray('store_error', []);
	}

	/**
	 * Retrieve the error string for the error when a duplicate is found in the database when
	 * storing
	 *
	 * @return string
	 */
	private function error_duplicate(): string {
		return strval($this->option('duplicate_error', '{indefinite_article} {name} with that name already exists. ({id})'));
	}

	protected function setMemberStoreError(string $member, string $message): self {
		$errors = $this->optionArray('store_error', []);
		$errors[$member] = $message;
		$this->setOption('store_error', $errors);
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
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_Store
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
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
			throw new Exception_ORM_Duplicate(get_class($this), $this->error_duplicate(), [
				'duplicate_keys' => $this->class->duplicate_keys, 'name' => $this->className(), 'id' => $this->id(),
				'indefinite_article' => $this->application->locale->indefinite_article($this->class->name),
			]);
		}
		$this->storeMembers();
		$this->callHook('store');
		/*
		 * Insert/Update
		 */
		if ($this->hasPrimaryKeys()) {
			$this->update();
		} else {
			$this->insert();
		}
		$this->store_queue();
		$this->is_new_cached = null;
		$this->storing = false;
		$this->original = $this->toDatabase($this->members);
		$this->callHook('stored');
		return $this;
	}

	/**
	 *
	 * @param ORM $that
	 * @return boolean
	 */
	public function isEqual(ORM $that): bool {
		return get_class($this) === $that::class && $this->id() === $that->id();
	}

	/**
	 * Store any objects which are members, first
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
					if (!$result->storing && ($result->is_new() || $result->changed()) && !$result->is_equal($this)) {
						$result->store();
					}
				} catch (Exception_ORM_NotFound $e) {
					$this->orm_not_found_exception($e, $member, $result);
				}
			}
		}
	}

	/**
	 * @return void
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_Store
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function beforeRegister(): void {
		foreach ($this->class->has_one as $member => $class) {
			if ($class[0] === '*') {
				$class = $this->member(substr($class, 1));
			}
			if (!empty($class)) {
				$object = $this->member($member);
				/* @var $object ORM */
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
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_Store
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 * @see ORM::statusExists
	 * @see ORM::status_insert
	 * @see ORM::status_unknown
	 */
	public function register(array|string $where = ''): self {
		// If we have all of our primary keys and has an auto_column, then don't bother registering.
		// Handles case when pre_register registers any objects within it. If everything is loaded OK, then we know
		// these are valid objects.
		if ($this->hasPrimaryKeys() && $this->class->auto_column && !$this->optionBool(self::option_ignore_auto_column)) {
			return $this;
		}
		$this->beforeRegister();

		try {
			$data = $this->exists($where);
			$result = $this->initialize($data, true)->polymorphicChild();
			return $result->setObjectStatus(self::STATUS_EXISTS);
		} catch (Exception_ORM_NotFound) {
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
		$id = $this->id();
		if (is_numeric($id)) {
			return strval($id);
		}
		if (is_array($id)) {
			ksort($id);
			$id = ArrayTools::flatten($id);
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
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
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
		$this->database()->delete($this->table, $where);
		if ($this->database()->affectedRows() === 0) {
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
	 * @throws Exception_Configuration
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
	 */
	public function walk(Walker $options): array {
		return $options->walk($this);
	}

	/**
	 * Traverse an ORM using various settings for generation of JSON.
	 *
	 * @param JSONWalker $options
	 * @return array
	 */
	public function json(JSONWalker $options): array {
		return $options->walk($this);
	}

	/**
	 * Load object
	 *
	 * @param Widget $source
	 * @return $this
	 * @throws Database_Exception_SQL
	 * @throws Exception_Configuration
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Permission
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function hook_control_loaded(Widget $source): self {
		/* @var $object ORM */
		$id = $source->request()->get($source->option('id_name', $this->class->id_column));
		if ($this->isNew() && !empty($id)) {
			$object = $this->initialize($id)->fetch();
			if (!$source->userCan('edit', $object)) {
				throw new Exception_Permission('edit', $object);
			}
		}
		return $this;
	}

	/**
	 * Hook to return a message when a control cancels editing
	 *
	 * @param Control $control
	 * @return string
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function hook_control_message_cancel(Control $control): string {
		$locale = $this->application->locale;
		$cancelMessage = $control->option('cancel_message', $locale->__('No changes were made to the {class_name-context-object-singular}.'));
		$cancelNewMessage = $control->option('cancel_new_message', $locale->__('{class_name-context-subject-singular} was not created.'));
		return $this->isNew() ? $cancelNewMessage : $cancelMessage;
	}

	/**
	 * Hook to return message
	 *
	 * @param Control $control
	 * @return string
	 */
	protected function hook_control_message_store(Control $control): string {
		$locale = $this->application->locale;
		$is_new = $this->isNew();
		$default_message = !$is_new ? $locale->__('Control:={class_name-context-subject-singular} "{display_name}" was updated.') : $locale->__('Control_ORM_Edit:={class_name-context-subject-singular} "{display_name}" was added.');
		$store_message = $control->option('store_message', $default_message);
		if ($is_new) {
			$store_message = $control->option('store_new_message', $store_message);
		}
		return $store_message;
	}

	/**
	 * Hook to return message related to store errors
	 *
	 * @param Control $control
	 * @return string
	 */
	protected function hook_control_message_store_error(Control $control): string {
		$locale = $this->application->locale;
		$name = strtolower($this->displayName());
		$message = $locale->__('{class_name-context-subject-indefinite-article} with that name already exists');
		$message = $this->option('store_error', $message);
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
		$rows['primary_keys'] = $this->id();
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
		$spec['class_name-raw'] = $name;
		$spec['class_name'] = $spec['class_name-singular'] = $locale->__($name);
		$spec['class_name-context-object'] = $spec['class_name-context-object-singular'] = $locale_class_name = strtolower($spec['class_name']);
		$spec['class_name-context-object-plural'] = $locale->plural($locale_class_name);
		$spec['class_name-context-subject'] = $spec['class_name-context-subject-singular'] = ucfirst($locale_class_name);
		$spec['class_name-context-subject-plural'] = ucfirst($spec['class_name-context-object-plural']);
		$spec['class_name-context-title'] = StringTools::capitalize($spec['class_name-context-object']);
		$spec['class_name-context-subject-indefinite-article'] = $locale->indefinite_article($name, true);
		$spec['class_name-plural'] = $locale->plural($name);

		$spec['display_name'] = $this->displayName();

		return $spec;
	}

	/**
	 * How to retrieve this object when passed as an argument to a router
	 *
	 * @param Route $route
	 * @param string $arg
	 * @return self
	 */
	protected function hook_router_argument(Route $route, string $arg): self {
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
		if ($mixed instanceof ORM) {
			return $mixed::class;
		}
		if ($mixed instanceof Class_ORM) {
			return $mixed->class;
		}
		if (is_string($mixed)) {
			return $mixed;
		}

		throw new Exception_Parameter('mixedToClass takes ORM ORMClass or String {type} given', ['type' => gettype($mixed)]);
	}

	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/* DEPRECATED BELOW
	 /*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return bool
	 * @deprecated 2022-05
	 * @codeCoverageIgnore
	 */
	public function is_linked(string $member, mixed $value): bool {
		return $this->isLinked($member, $value);
	}

	/**
	 * Retrieve a member as a boolean value
	 *
	 * @param $member string
	 *            Name of member
	 * @param $def mixed
	 *            Default value to return if can't convert to boolean
	 * @return bool
	 * @throws Exception_Key
	 * @throws Exception_Deprecated
	 * @deprecated 2022-05
	 * @codeCoverageIgnore
	 */
	public function member_boolean(string $member, mixed $def = null): bool {
		if ($def !== null) {
			throw new Exception_Deprecated('member_boolean no longer supports default values');
		}
		return $this->memberBool($member);
	}

	/**
	 * Retrieve a member as a timestamp value
	 *
	 * @param $member string
	 *            Name of member
	 * @param $def mixed
	 *            Use this value if member does not exist
	 * @return Timestamp
	 * @codeCoverageIgnore
	 */
	public function member_timestamp(string $member, mixed $def = null): Timestamp {
		if ($def !== null) {
			throw new Exception_Deprecated('default parameter for ' . __METHOD__);
		}
		return $this->memberTimestamp($member);
	}

	/**
	 * Getter/setter for serialized array attached to an object
	 *
	 * @param string $member
	 * @param array|string|null $mixed
	 * @param mixed|null $value
	 * @return mixed
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @codeCoverageIgnore
	 * @deprecated 2022-05
	 */
	public function member_data(string $member, array|string $mixed = null, mixed $value = null): mixed {
		$data = toArray($this->member($member));
		if (is_array($mixed)) {
			return $this->setMemberData($member, $mixed, boolval($value));
		} elseif (is_string($mixed)) {
			if ($value === null) {
				// Value null in this context means ->get($mixed)
				return avalue($data, $mixed);
			} else {
				// Value non-null  in this context means ->set($mixed, $value)
				$data[$mixed] = $value;
				return $this->setMemberData($member, $data);
			}
		}
		return $this->member($member);
	}

	/**
	 * @param $member
	 * @return void
	 * @codeCoverageIgnore
	 * @deprecated 2022-05
	 */
	public function memberKeysRemove(string|array $member): void {
		$member = toList($member);
		foreach ($member as $m) {
			unset($this->members[$m]);
		}
	}

	/**
	 * Retrieve a member as an integer
	 *
	 * @param string $member
	 * @param mixed|null $def
	 * @codeCoverageIgnore
	 * @return mixed
	 * @throws Exception_Deprecated
	 * @throws Exception_Semantics
	 */
	public function member_integer(string $member, mixed $def = null): mixed {
		try {
			return $this->memberInteger($member);
		} catch (Exception_Key) {
			return $def;
		}
	}

	/**
	 * Retrieve a query for the current object
	 *
	 * @param string $alias
	 * @return Database_Query_Select
	 * @deprecated 2022-05
	 * @see ORM::querySelect()
	 * @codeCoverageIgnore
	 */
	public function query_select(string $alias = ''): Database_Query_Select {
		return $this->querySelect($alias);
	}

	/**
	 * Create an insert query for this object
	 *
	 * @return Database_Query_Insert
	 * @deprecated 2022-05
	 * @see ORM::queryInsert()
	 * @codeCoverageIgnore
	 */
	public function query_insert(): Database_Query_Insert {
		return $this->queryInsert();
	}

	/**
	 * Create an update query for this object
	 *
	 * @return Database_Query_Update
	 * @deprecated 2022-05
	 * @see ORM::queryUpdate()
	 * @codeCoverageIgnore
	 */
	public function query_update(string $alias = ''): Database_Query_Update {
		return $this->queryUpdate($alias);
	}

	/**
	 * Create an insert -> select query for this object
	 *
	 * @param string $alias
	 * @return Database_Query_Insert_Select
	 * @codeCoverageIgnore
	 * @deprecated 2022-05
	 */
	public function query_insert_select(string $alias = ''): Database_Query_Insert_Select {
		return $this->queryInsertSelect($alias);
	}

	/**
	 * Create an delete query for this object
	 *
	 * @return Database_Query_Delete
	 * @deprecated 2022-05
	 * @see ORM::queryDelete()
	 * @codeCoverageIgnore
	 */
	public function query_delete(): Database_Query_Delete {
		return $this->queryDelete();
	}

	/**
	 * Always use UTC timestamps when setting dates for this object
	 *
	 * @return boolean
	 * @deprecated 2022-05
	 * @codeCoverageIgnore
	 */
	public function utc_timestamps(): bool {
		return $this->utcTimestamps();
	}

	/**
	 * Returns name of the database used by this object
	 *
	 * @return string
	 * @see ORM::databaseName()
	 * @deprecated 2022-01
	 * @codeCoverageIgnore
	 */
	public function database_name(): string {
		return $this->databaseName();
	}

	/**
	 * Set a member to a value
	 *
	 * @param string $member
	 * @param mixed $v
	 * @param boolean $overwrite
	 * @return $this
	 * @deprecated 2022-05
	 * @codeCoverageIgnore
	 */
	public function set_member($member, $v = null, $overwrite = true) {
		$this->refresh();
		if (is_array($member)) {
			foreach ($member as $k => $v) {
				$this->set_member($k, $v, $overwrite);
			}
		} else {
			return $this->setMember($member, $v, $overwrite);
		}
		return $this;
	}

	/**
	 * @return array
	 * @deprecated 2022-05
	 * @see ORM::inheritOptions()
	 * @codeCoverageIgnore
	 */
	public function inherit_options(): array {
		$this->application->deprecated(__METHOD__);
		return $this->inheritOptions();
	}

	/**
	 * Does this object have all primary keys set to a value?
	 *
	 * @return boolean
	 * @deprecated 2022-05
	 * @codeCoverageIgnore
	 */
	public function has_primary_keys(): bool {
		$this->application->deprecated(__METHOD__);
		return $this->hasPrimaryKeys();
	}

	/**
	 * Set/get result of object operation
	 *
	 * @return string
	 * @deprecated 2022-11
	 * @codeCoverageIgnore
	 */
	public function object_status(): string {
		$this->application->deprecated(__METHOD__);
		return $this->status;
	}

	/**
	 * Have any of the members given changed in this object?
	 *
	 * @param array|string $members
	 *            Array or list of members
	 * @return boolean
	 * @deprecated 2022-05
	 * @see self::membersChanged
	 * @codeCoverageIgnore
	 */
	public function members_changed(array|string $members): bool {
		$this->application->deprecated(__METHOD__);
		return $this->membersChanged($members);
	}
}
