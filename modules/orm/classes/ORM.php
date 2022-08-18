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
	public const object_status_exists = 'exists';

	/**
	 * Previous call resulted in the saving of the existing object in the database
	 *
	 * @see ORM::register
	 * @see ORM::fetch_if_exists
	 * @var string
	 */
	public const object_status_insert = 'insert';

	/**
	 * Previous call failed or has an unknown result
	 *
	 * @see ORM::register
	 * @see ORM::fetch_if_exists
	 * @var string
	 */
	public const object_status_unknown = 'failed';

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
		return $this->modelFactory($class, $mixed, $options);
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
				'ORM::class' => get_class($this),
				__CLASS__ . '::class' => get_class($this),
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
	public function cache($key = null, $data = null) {
		if ($key === null) {
			return to_array($this->call_hook('cache_list'), []);
		} elseif ($data === null) {
			return $this->call_hook_arguments('cache_load', [$key, ], null);
		} else {
			$this->call_hook('cacheSave', $key, $data);
			return $this;
		}
	}

	/**
	 * Cache object data
	 */
	public function cache_dirty($key = null): void {
		$this->call_hook('cache_dirty', $key);
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
	public function cache_output_begin($key = null) {
		$data = $this->cache($key);
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
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function cache_output_end() {
		if (count($this->cache_stack) === 0) {
			throw new Exception_Semantics(get_class($this) . '::cache_output_end before cache_output_begin');
		}
		$content = ob_get_flush();
		$key = array_pop($this->cache_stack);
		return $this->cache($key, $content);
	}

	/**
	 *
	 * @return Database
	 */
	public function database(): Database {
		if ($this->database instanceof Database) {
			return $this->database;
		}
		return $this->database = $this->application->database_registry($this->database_name);
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
	public function name() {
		$name_col = $this->name_column();
		if (empty($name_col)) {
			return null;
		}
		return $this->_get($name_col);
	}

	/**
	 * Retrieve the name column for this object (if any)
	 *
	 * @return string|null
	 */
	final public function name_column() {
		return $this->class->name_column;
	}

	/**
	 * Retrieves the single find key for an object, if available.
	 * (Multi-key finds always return null)
	 *
	 * @return string|null
	 */
	final public function find_key() {
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
	final public function find_keys() {
		return $this->class->find_keys;
	}

	/**
	 * Retrieve list of member names used to find a duplicate object in the database
	 *
	 * @return array:string
	 */
	final public function duplicate_keys() {
		return $this->class->duplicate_keys;
	}

	/**
	 * Returns valid member names for this database table
	 *
	 * Includes dynamic fields including iterators and has_one/has_many/getters/setters
	 *
	 * @return array
	 */
	public function member_names() {
		return $this->class->member_names();
	}

	/**
	 * Return just database columns for this object
	 *
	 * @return array
	 */
	public function columns() {
		return array_keys($this->class->column_types);
	}

	/**
	 * Name of this object's class (where is this used?)
	 *
	 * @return string
	 */
	public function class_name() {
		return $this->class->name;
	}

	/**
	 * If there's an ID column, return the name of the column
	 *
	 * @return string
	 */
	public function idColumn(): string {
		return $this->class->id_column;
	}

	/**
	 * Does this object have all primary keys set to a value?
	 *
	 * @return boolean
	 */
	public function hasPrimaryKeys() {
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
	 * Does this object have all primary keys set to a value?
	 *
	 * @return boolean
	 * @deprecated 2022-05
	 */
	public function has_primary_keys(): bool {
		return $this->hasPrimaryKeys();
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
		return $this->primaryKeys();
	}

	/**
	 * Class code name
	 *
	 * @return string
	 * @deprecated 2022-05
	 */
	public function class_code_name(): string {
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
	 * @throws Exception_Semantics
	 */
	public function selectDatabase() {
		$db = $this->database();
		if (!$db) {
			throw new Exception_Semantics('No database configured');
		}
		return $db->selectDatabase($db->databaseName());
	}

	/**
	 * Ensure this object is loaded from database if needed
	 * @return self
	 */
	public function refresh(): self {
		if ($this->need_load && $this->can_fetch()) {
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
			$this->call_hook('initialized');
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
				'what' => ['*X' => 'COUNT(*)', ],
				'tables' => $this->table(),
				'where' => $where,
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
	public function clear() {
		$this->members = $this->class->column_defaults;
		$this->store_queue = [];
		return $this;
	}

	/**
	 * Ouptut the display name for this object.
	 *
	 * @return string
	 */
	public function display_name() {
		$name_column = $this->class->name_column;
		if (!$name_column) {
			return '';
		}
		return $this->member($name_column);
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
		$idcol = $this->class->id_column;
		if (is_string($idcol)) {
			return $this->set($idcol, $set);
		}

		/**
		 * Passing a string or list of values to load
		 */
		$pk = $this->class->primary_keys;
		if (is_string($set) || ArrayTools::isList($set)) {
			$ids = toList($set);
			if (count($ids) !== count($pk)) {
				$this->application->logger->warning('{class}::id("{set}") mismatches primary keys (expected {npk})', [
					'class' => get_class($this),
					'set' => $set,
					'npk' => count($pk),
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
					'class' => get_class($this),
					'set' => JSON::encode($set),
					'ks' => implode(',', $missing),
				]);
			}
			$this->setMembers($set);
			return $this;
		}

		throw new Exception_Parameter('{class}::id("{value}" {type}) unknown parameter: ', [
			'class' => get_class($this),
			'value' => _dump($set),
			'type' => type($set),
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
		$query->orm_class(get_class($this));
		$query->orm_class_options($this->inheritOptions());
		return $query->into($this->table())->valid_columns($this->columns());
	}

	/**
	 * Create an insert -> select query for this object
	 *
	 * @param string $alias
	 * @return Database_Query_Insert_Select
	 */
	public function queryInsertSelect(string $alias = ''): Database_Query_Insert_Select {
		$query = new Database_Query_Insert_Select($this->database());
		$query->orm_class(get_class($this));
		$query->orm_class_options($this->inheritOptions());
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
		$query->setORMClassOptions($this->inherit_Options());
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
	 * @param $alias string
	 * @return ORMIterator
	 */
	public function iterator(Database_Query_Select $query, array $options = []): ORMIterator {
		$class = $options['iterator_class'] ?? ORMIterator::class;
		return $this->application->factory($class, get_class($this), $query, $this->inheritOptions() + $options);
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
	protected function memberIterator(string $member, array $where = []) {
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
	 * @param $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Select
	 */
	public function memberQuery(string $member, ORM &$object = null): Database_Query_Select {
		return $this->class->memberQuery($this, $member, $object);
	}

	/**
	 * Create a query for an object's member
	 *
	 * @param $member string
	 *            Many member
	 * @param $object ORM
	 *            ORM related to this member, optionally returned
	 * @return Database_Query_Update
	 * @todo Unimplemented
	 */
	public function memberQueryUpdate(string $member, ORM &$object = null): Database_Query_Update {
		return $this->class->memberQueryUpdate($this, $member, $object);
	}

	/**
	 *
	 * @param string $member
	 */
	private function memberForeignList(string $member) {
		if ($this->is_new()) {
			return array_keys(to_array(avalue($this->members, $member, [])));
		}
		return $this->class->memberForeignList($this, $member);
	}

	private function memberForeignExists($member, $id) {
		if ($this->is_new()) {
			return apath($this->members, [$member, $id, ]) !== null;
		}
		return $this->class->memberForeignExists($this, $member, $id);
	}

	private function memberForeignDelete($member): void {
		$queue = $this->class->memberForeignDelete($this, $member);
		if (is_array($queue)) {
			$this->store_queue += $queue;
		}
		//		if ($this->is_new()) {
		$this->members[$member] = [];
		//		}
	}

	private function memberForeignAdd($member, ORM $object): void {
		$foreign_keys = $object->members($object->primary_keys());
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
	 * @return ORM|null
	 */
	public function member_model_factory(string $member, string $class, mixed $mixed = null, array $options = []): ?Model {
		return $this->ormFactory($class, $mixed, $options); //->refresh();
	}

	/**
	 *
	 * @param Exception_ORM_NotFound $e
	 * @param ?string $member
	 * @return NULL
	 * @throws \zesk\Exception_ORM_NotFound
	 */
	private function orm_not_found_exception(Exception_ORM_NotFound $e, string $member = null, mixed $data = null): void {
		if ($this->optionBool('fix_orm_members') || $this->optionBool('fix_member_objects')) {
			// Prevent infinite recursion
			$magic = '__' . __METHOD__;
			if (avalue($this->members, $magic)) {
				return;
			}
			$this->original[$magic] = true;
			$this->members[$magic] = true;
			$application = $this->application;
			$application->hooks->call('exception', $e);
			$application->logger->error("Fixing not found {member} {member_class} (#{data}) in {class} (#{id})\n{bt}", [
				'member' => $member,
				'member_class' => $member::class,
				'data' => $data,
				'class' => get_class($this),
				'id' => $this->id(),
				'bt' => _backtrace(),
			]);
			if ($member) {
				$this->members[$member] = null;
			}
			$this->store();
			unset($this->original[$magic]);
			unset($this->members[$magic]);
		} else {
			throw $e;
		}
	}

	/**
	 * Retrieve a member which is another ORM
	 *
	 * @param string $member
	 * @param mixed $options
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	final protected function member_object(string $member, array $options = []): ORM {
		$this->refresh();
		$data = $this->members[$member] ?? null;
		if (!$data) {
			throw new Exception_Key($member);
		}
		if (!array_key_exists($member, $this->class->has_one)) {
			throw new Exception_Semantics('Accessing {class}::member_object but {member} is not in has_one', [
				'class' => get_class($this),
				'member' => $member,
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
				'class' => get_class($this),
				'member' => $member,
				'data' => $data,
			]);
		}

		try {
			$object = $this->member_model_factory($member, $class, $data, $options + $this->inheritOptions());
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
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics|Exception_Configuration
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
			return $this->member_object($member, $this->inheritOptions());
		}
		return $this->member($member);
	}

	/**
	 * May be overridden in subclasses to abstract away model.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed {
		try {
			return $this->_get($key);
		} catch (Exception_Key|Exception_Semantics) {
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
					'class' => get_class($this),
					'keys' => $find_keys,
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
				$value,
				$key,
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
							'member' => $dynamic_member,
							'class' => get_class($this),
							'value' => $value,
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
			'member' => $member,
			'class' => get_class($this),
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
	 * @throws Exception_Deprecated
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
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
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
	 * @throws Exception_Deprecated
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
			'value' => $value,
			'type' => type($value),
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
	 * @throws Exception_Deprecated
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
	 * @throws Exception_Deprecated
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
	 * Have any of the members given changed in this object?
	 *
	 * @param array|string $members
	 *            Array or list of members
	 * @return boolean
	 * @deprecated 2022-05
	 * @see self::membersChanged
	 */
	public function members_changed(array|string $members): bool {
		return $this->membersChanged($members);
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
	 * @throws Exception_Deprecated
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
	 * @return $this
	 * @throws Exception_Key
	 */
	private function _setStoreMember(string $member, bool $store): self {
		if (!array_key_exists($member, $this->store_columns)) {
			throw new Exception_Key($member);
		}
		$this->store_columns[$member] = $store;
		return $this;
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
		$limit = min($this->option('dupliate_rename_limit', 100), 1000);
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
				'(.*)',
				'([ 0-9]*)',
			]) . '$#';
		$matches = null;
		// If pattern found, pull out new base name (e.g. "Foo (Copy 2)" => "Foo"
		$base_name = preg_match($preg_pattern, $name, $matches) ? $matches[1] : $name;
		// Gather patterns to be used for new names (must include spacing if needed
		$patterns = $this->call_hook_arguments('duplicate_rename_patterns', [], null);
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
	 * @param Options_Duplicate|null $options
	 * @return self
	 * @throws Exception_Deprecated
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	protected function duplicate(Options_Duplicate &$options = null): self {
		if ($options === null) {
			$options = new Options_Duplicate($this->inheritOptions());
		}
		$member_names = ArrayTools::valuesRemove(array_keys($this->class->column_types), $this->class->primary_keys);
		$this->application->logger->debug('member_names={names}', ['names' => $member_names, ]);
		$new_object = $this->ormFactory(get_class($this), $this->members($member_names), array_merge($this->inheritOptions(), $options->options()));
		$options->processDuplicate($new_object);
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
	 */
	/**
	 * @return array
	 * @throws Exception_Semantics
	 */
	protected function pre_insert() {
		$members = $this->call_hook_arguments('pre_insert', [$this->members, ], $this->members);
		$members = $this->_filterStoreMembers($members);
		$this->selectDatabase();
		return $this->toDatabase($members, true);
	}

	/**
	 * @return int 0 if noop, < 0 if no ID
	 * @throws Database_Exception
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_ORM_Duplicate
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_Store
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	private function insert(): int {
		if ($this->optionBool('disable_database') || $this->optionBool('disable_database_insert')) {
			return 0;
		}
		$members = $this->pre_insert();
		if (count($members) === 0) {
			throw new Exception_ORM_Empty(get_class($this), '{class}: All members: {members} Store members: {store}', [
				'members' => array_keys($this->members),
				'store' => $this->store_columns,
			]);
		}

		try {
			if ($this->class->auto_column) {
				$auto_id = $this->database()->insert($this->table(), $members);
				if ($auto_id > 0) {
					$this->setMember($this->class->auto_column, $auto_id);
					$this->call_hook('insert');
					return $auto_id;
				}

				throw new Exception_ORM_Store(get_class($this), 'Unable to insert (no id)');
			}
			$result = $this->database()->insert($this->table(), $members, ['id' => false, ]);
			$this->call_hook('insert');
			return $result;
		} catch (Database_Exception_Duplicate $e) {
			$this->call_hook('insert_failed', $e);

			throw new Exception_ORM_Duplicate(get_class($this), $e->getMessage());
		}
	}

	/**
	 * @return bool
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
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
				'class' => get_class($this),
				'primary_keys' => implode(', ', $this->class->primary_keys),
			]));
		}
		foreach ($members as $member => $value) {
			if (begins($member, '*')) {
				continue;
			}
			if (!array_key_exists($member, $this->original)) {
				continue;
			}
			if ($value === $this->original[$member]) {
				unset($members[$member]);
			}
		}
		$members = $this->call_hook('update_alter', $members);
		if (count($members) === 0) {
			if (self::$debug) {
				$this->application->logger->debug('Update of {class}:{id} - no changes', [
					'class' => get_class($this),
					'id' => $this->id(),
				]);
			}
			return;
		}

		try {
			$result = $this->database()->update($this->table(), $members, $where);
			$this->call_hook('update', $members);
			$this->original = $this->members + $this->original;
			return;
		} catch (\Exception $e) {
			$this->call_hook('update_failed');

			throw $e;
		}
	}

	/**
	 * Returns a new object which contains the found ORM, or null if not found
	 *
	 * @param array $where How to find this object (uses default ->exists where clause)
	 * @return self
	 * @throws Exception_ORM_NotFound
	 *
	 */
	public function find(array $where = []): ORM {
		$data = $this->exists($where);
		return $this->initialize($data, true)->polymorphicChild();
	}

	public function fetch_if_exists($where = null) {
		$row = $this->exists($where);
		if (is_array($row)) {
			return $this->object_status(self::object_status_exists)->initialize($row, true);
		}
		$this->object_status(self::object_status_unknown);
		return null;
	}

	/**
	 * @param string|array $where
	 * @return array
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORM_NotFound
	 * @throws Exception_Semantics
	 */
	public function exists(string|array $where = ''): array {
		if (is_string($where) && !empty($where)) {
			if ($this->hasMember($where)) {
				$where = [$where => $this->member($where), ];
			}
		}
		if (!is_array($where)) {
			$find_keys = $this->class->find_keys;
			if (empty($find_keys)) {
				throw new Exception_Semantics('No find keys for class {class}', ['class' => get_class($this)]);
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
			return $query->one(null);
		} catch (Database_Exception_SQL|Exception_Key|Exception_Semantics) {
			throw new Exception_ORM_NotFound(get_class($this));
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
	 * @throws Exception_ORM_NotFound
	 */
	public function fetchByKey(mixed $value = null, string $column = ''): ORM {
		if (empty($column)) {
			$column = $this->find_key();
			if (empty($column)) {
				$column = $this->class->id_column;
			}
		}

		try {
			return $this->initialize($this->exists([$column => $value, ]), true)->polymorphicChild();
		} catch (Exception_Parameter|Database_Exception_SQL|Exception_Semantics|Exception_Key $previous) {
			throw new Exception_ORM_NotFound(get_class($this), 'fetchByKey({value}, {column})', [
				'value' => $value,
				'column' => $column,
			], 0, $previous);
		}
	}

	/**
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Unimplemented
	 */
	protected function fetch_query() {
		$primary_keys = $this->class->primary_keys;
		if (count($primary_keys) === 0) {
			throw new Exception_Semantics('{get_class} {method} can not access fetch_query when there\'s no primary keys defined', [
				'get_class' => get_class($this),
				'method' => __METHOD__,
			]);
		}
		$keys = $this->members($primary_keys);
		$sql = $this->sql()->select(['what' => '*', 'tables' => $this->table(), 'where' => $keys, 'limit' => 1, ]);
		return $sql;
	}

	/**
	 * @param array $data
	 * @param $insert
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
	 * @param ?string $set Set polymorphic class - used internally from Class_ORM
	 * @return self|boolean
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
				'polymorphic_leaf' => $this->polymorphic_leaf,
				'class' => get_class($this),
			]);
			$this->application->hooks->call('exception', $e);
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

	/**
	 * @param array $mixed
	 * @return $this
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 */
	public function fetch(array $mixed = []): self {
		$mixed = $this->call_hook('fetch_enter', $mixed);
		if (count($mixed) !== 0) {
			$this->initialize($mixed)->polymorphicChild();
		}
		$hook_args = func_get_args();
		$this->need_load = false;
		if (!$this->can_fetch()) {
			throw new Exception_ORM_Empty(get_class($this), '{class}: Missing primary key {primary_keys} values: {values}', [
				'primary_keys' => $this->class->primary_keys,
				'values' => $this->members($this->class->primary_keys),
			]);
		}
		$this->selectDatabase();
		$obj = $this->fetch_object();
		if (!$obj) {
			if (($result = $this->call_hook_arguments('fetch_not_found', $hook_args, null)) !== null) {
				return $result;
			}

			throw new Exception_ORM_NotFound(get_class($this), 'Fetching {id}', $this->variables());
		}
		if ($this->_deleted($obj)) {
			if (($result = $this->call_hook_arguments('fetch_deleted', $hook_args, null)) !== null) {
				return $result;
			}

			$this->orm_not_found_exception(new Exception_ORM_NotFound(get_class($this)), '-this-', $this->id());
		}
		$result = $this->initialize($obj, true)->polymorphicChild();
		return $result->call_hook_arguments('fetch', $hook_args, $result);
	}

	protected function fetch_object() {
		$sql = $this->fetch_query();
		return $this->database()->queryOne($sql);
	}

	/**
	 * Retrieve errors during storing object
	 *
	 * @return array
	 */
	public function store_errors() {
		return $this->optionArray('store_error', []);
	}

	/**
	 * Retrieve the error string for the error when a duplicate is found in the database when
	 * storing
	 *
	 * @return string
	 */
	private function error_duplicate() {
		return $this->option('duplicate_error', '{indefinite_article} {name} with that name already exists. ({id})');
	}

	protected function error_store($member, $message) {
		$errors = $this->optionArray('store_error', []);
		$errors[$member] = $message;
		$this->setOption('store_error', $errors);
		return null;
	}

	protected function store_queue(): void {
		foreach ($this->store_queue as $q) {
			$func = array_shift($q);
			call_user_func_array([$this, $func, ], $q);
		}
		$this->store_queue = [];
	}

	/**
	 *
	 * x     * @return $this
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
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
				'duplicate_keys' => $this->class->duplicate_keys,
				'name' => $this->class_name(),
				'id' => $this->id(),
				'indefinite_article' => $this->application->locale->indefinite_article($this->class->name),
			]);
		}
		$this->store_object_members();
		$this->call_hook('store');
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
		$this->call_hook('stored');
		return $this;
	}

	/**
	 *
	 * @param ORM $orm
	 * @return boolean
	 */
	public function is_equal(ORM $that) {
		return get_class($this) === $that::class && $this->id() === $that->id();
	}

	/**
	 * Store any objects which are members, first
	 */
	private function store_object_members(): void {
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

	protected function pre_register(): void {
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
	 * @return self The ID of the registered object. Also the status is set to what happened, see
	 *         self::status_foo definitions either "insert", or "exists".
	 * @see ORM::status_exists
	 * @see ORM::status_insert
	 * @see ORM::status_unknown
	 */
	public function register($where = null) {
		// If we have all of our primary keys and has an auto_column, then don't bother registering.
		// Handles case when pre_register registers any objects within it. If everything is loaded OK, then we know
		// these are valid objects.
		if ($this->has_primary_keys() && $this->class->auto_column && !$this->optionBool(self::option_ignore_auto_column)) {
			return $this;
		}
		$this->pre_register();
		$data = $this->exists($where);
		if ($data === null) {
			try {
				$result = $this->store();
				if (!$result) {
					$this->object_status(self::object_status_unknown);
					return null;
				}
				return $result->object_status(self::object_status_insert);
			} catch (Database_Exception_Duplicate $e) {
				$data = $this->exists($where);
				if ($data === null) {
					throw $e;
				}
			}
			$result = $this->initialize($data, true)->polymorphicChild()->store();
		} else {
			$result = $this->initialize($data, true)->polymorphicChild();
		}
		return $result->object_status(self::object_status_exists);
	}

	/**
	 * Set/get result of object operation
	 *
	 * @param string $set
	 * @return string|$this
	 */
	public function object_status($set = null) {
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
	public function status_exists() {
		return $this->status === self::object_status_exists;
	}

	/**
	 *
	 * @return boolean
	 */
	public function status_created() {
		return $this->status === self::object_status_insert;
	}

	private function _column_deleted_value() {
		return [$this->class->column_deleted => true, ];
		// TODO: Support dates
	}

	/**
	 * Delete rows for this object where
	 *
	 * @param string $column
	 * @param string $class
	 * @return Database_Query_Delete
	 * @todo ID should not be hardcoded below. Is this used? 2018-01 KMD
	 *
	 */
	protected function delete_unlinked_column($column, $class) {
		$link_class = $this->application->class_ormRegistry($class);
		$link_id_column = $link_class->id_column;
		if (!$link_id_column) {
			$__ = ['method' => __METHOD__, 'column' => $column, 'class' => $class, ];

			throw new Exception_Semantics('{method}({column}, {class}): {class} does not have an id column', $__);
		}
		$unlinked = $this->querySelect()->link($class, [
			'alias' => 'Link',
			'require' => false,
		])->addWhere('Link.' . $link_id_column, null)->addWhat($column, $column)->toArray(null, $column);
		if (count($unlinked) === 0) {
			return 0;
		}
		return $this->queryDelete()->addWhere($column, $unlinked)->execute()->affectedRows();
	}

	/**
	 * For each of the "has_one" - if the target object does not exist, then delete this object, too
	 *
	 * Use with caution!
	 */
	protected function delete_unlinked() {
		$result = [];
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
			$this->call_hook('delete_already');
			return;
		}
		$this->call_hook('delete');
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
	 *            An array of link settings, or a string indicating the path to link to
	 *            The settings in the array are:
	 *            <code>
	 *            "path" => "ORM_Member.NextORM_Member.Column"
	 *            </code>
	 * @return Database_Query_Select
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
	public function walk(Walker $options) {
		return $options->walk($this);
	}

	/**
	 * Traverse an ORM using various settings for generation of JSON.
	 *
	 * @param JSONWalker $options
	 * @return array
	 */
	public function json(JSONWalker $options) {
		return $options->walk($this);
	}

	/**
	 * Load object
	 *
	 * @param Widget $source
	 * @return $this
	 */
	protected function hook_control_loaded(Widget $source) {
		/* @var $object ORM */
		$id = $source->request()->get($source->option('id_name', $this->class->id_column, null));
		if ($this->is_new() && !empty($id)) {
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
	 */
	protected function hook_control_message_cancel(Control $control) {
		$locale = $this->application->locale;
		$cancelMessage = $control->option('cancel_message', $locale->__('No changes were made to the {class_name-context-object-singular}.'));
		$cancelNewMessage = $control->option('cancel_new_message', $locale->__('{class_name-context-subject-singular} was not created.'));
		return $this->is_new() ? $cancelNewMessage : $cancelMessage;
	}

	/**
	 * Hook to return message
	 *
	 * @param Control $control
	 * @return Ambigous <Model, Model, mixed, Hookable, string, array, number>
	 */
	protected function hook_control_message_store(Control $control) {
		$locale = $this->application->locale;
		$is_new = $this->is_new();
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
	protected function hook_control_message_store_error(Control $control) {
		$locale = $this->application->locale;
		$name = strtolower($this->display_name());
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
				'title' => $locale->__('View {object}', $__),
				'class' => $class,
				'before_hook' => ['allowed_if_all' => ["$class::view all", ], ],
			],
			$prefix . 'view all' => ['title' => $locale->__('View all {objects}', $__), ],
			$prefix . 'edit' => [
				'title' => $locale->__('Edit {object}', $__),
				'class' => $class,
				'before_hook' => ['allowed_if_all' => ["$class::edit all", ], ],
			],
			$prefix . 'edit all' => ['title' => $locale->__('Edit all {objects}', $__), ],
			$prefix . 'new' => ['title' => $locale->__('Create {objects}', $__), ],
			$prefix . 'delete all' => ['title' => $locale->__('Delete any {objects}', $__), ],
			$prefix . 'delete' => [
				'title' => $locale->__('Delete {object}', $__),
				'before_hook' => ['allowed_if_all' => ["$class::delete all", ], ],
				'class' => $class,
			],
			$prefix . 'list' => ['title' => $locale->__('List {objects}', $__), ],
		];
	}

	/**
	 *
	 * @return string
	 * @see Debug::_dump
	 */
	public function _debug_dump() {
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

		$name = $this->display_name();
		$spec['display_name'] = $name;

		return $spec;
	}

	/**
	 * How to retrieve this object when passed as an argument to a router
	 *
	 * @param Route $route
	 * @param string $arg
	 * @return self
	 */
	protected function hook_router_argument(Route $route, $arg) {
		return $this->id($arg)->fetch();
	}

	/**
	 * Name/value pairs used to generate the schema for this object
	 *
	 * @return array
	 */
	public function schema_map() {
		return $this->optionArray('schema_map') + ['table' => $this->table(), ];
	}

	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/* DEPRECATED BELOW
	 /*==================================================================================================================================*/
	/*==================================================================================================================================*/
	/*==================================================================================================================================*/

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

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return bool
	 * @deprecated 2022-05
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
	 * @deprecated 2022-05
	 */
	public function member_data(string $member, array|string $mixed = null, mixed $value = null): mixed {
		$data = to_array($this->member($member));
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
	 */
	public function query_update(string $alias = ''): Database_Query_Update {
		return $this->queryUpdate($alias);
	}

	/**
	 * Create an insert -> select query for this object
	 *
	 * @param string $alias
	 * @return Database_Query_Insert_Select
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
	 */
	public function query_delete(): Database_Query_Delete {
		return $this->queryDelete();
	}

	/**
	 * Always use UTC timestamps when setting dates for this object
	 *
	 * @return boolean
	 * @deprecated 2022-05
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
	 */
	public function inherit_options(): array {
		$this->application->deprecated(__METHOD__);
		return $this->inheritOptions();
	}
}
