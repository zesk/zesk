<?php

/**
 * 
 */
namespace zesk;

/**
 *
 * @author kent
 *        
 */
abstract class Cache implements \ArrayAccess {
	
	/**
	 * Bump when incompatibilities arise
	 *
	 * @var integer
	 */
	const version = 1;
	
	/**
	 */
	abstract protected function exists();
	
	/**
	 */
	abstract protected function fetch();
	
	/**
	 *
	 * @param mixed $data        	
	 */
	abstract protected function store($data);
	
	/**
	 * Expire this cache entry after the specified amount of time (in seconds)
	 *
	 * @param integer $n_seconds
	 *        	Seconds after initial creation that this cache object should be deleted
	 */
	abstract public function expire_after($n_seconds);
	
	/**
	 * Delete cache
	 */
	abstract protected function delete();
	
	/**
	 *
	 * @var boolean
	 */
	public static $disabled = false;
	
	/**
	 *
	 * @var boolean
	 */
	public static $bucket = null;
	
	/**
	 *
	 * @var array
	 */
	protected static $caches = array();
	
	/**
	 * Name of cache
	 * 
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Whether this cache has changed and needs to be written at the end of the request
	 * 
	 * @var boolean
	 */
	protected $_dirty;
	
	/**
	 * Whether this cache object has been loaded from disk
	 * 
	 * @var boolean
	 */
	protected $_load;
	
	/**
	 * The cache data
	 * 
	 * @var array
	 */
	protected $_data;
	
	/**
	 * The cache internal data
	 * 
	 * @var array
	 */
	protected $_internal = array();
	
	/**
	 * The time() this was created
	 * 
	 * @var integer
	 */
	protected $_created;
	
	/**
	 * Registered Cache interfaces
	 *
	 * @var string
	 */
	static $interfaces = array(
		"file" => "Cache_File"
	);
	
	/**
	 * Class to create for caches
	 * 
	 * @var string
	 */
	static $instance_class = null;
	
	/**
	 * A global identifier to segment one application from another on shared systems.
	 *
	 * All cache operations are restricted to within a bucket.
	 *
	 * @param string $set        	
	 * @return string
	 */
	public static function bucket($set = null) {
		if ($set !== null) {
			self::$bucket = $set;
			self::$caches = array();
			self::static_call("static_bucket", $set);
		}
		return self::$bucket;
	}
	
	/**
	 * Disable cache, or get disabled state of cache
	 *
	 * @param boolean $set
	 *        	Optional value to set the cache to
	 * @return boolean
	 */
	public static function disabled($set = null) {
		if ($set !== null) {
			self::$disabled = to_bool($set);
			if (self::$disabled) {
				self::$caches = array();
			}
		}
		return self::$disabled;
	}
	
	/**
	 * Register a cache interface
	 *
	 * @param string $name        	
	 * @param string $class        	
	 * @return array
	 */
	public static function register_interface($name, $class) {
		self::$interfaces[$name] = $class;
		return self::$interfaces;
	}
	
	/**
	 *
	 * @return Cache_Interface
	 */
	private static function init_instance() {
		if (!self::$instance_class) {
			if (is_array(self::$interfaces)) {
				global $zesk;
				/* @var $zesk Kernel */
				$type = to_list($zesk->configuration->path_get(__CLASS__ . "::interface", "file"));
				foreach ($type as $code) {
					if (array_key_exists($code, self::$interfaces)) {
						self::$instance_class = self::$interfaces[$code];
						if (self::static_call('static_installed') === false) {
							continue;
						}
						return;
					}
				}
			}
			self::$instance_class = "Cache_File";
		}
	}
	
	/**
	 * Retrieve the instance class
	 *
	 * @param string $name        	
	 * @return Cache
	 */
	private static function instance($name) {
		self::init_instance();
		return new self::$instance_class($name);
	}
	
	/**
	 * Create a new cache object.
	 * Used internally. Use Cache::register to create a Cache object.
	 * 
	 * @see Cache::register
	 * @param string $name
	 *        	The name of this cache
	 */
	private function __construct($name) {
		zesk()->hooks->register_class(__CLASS__);
		$this->_name = path(self::$bucket, $name);
		$this->initialize();
	}
	
	/**
	 * Reset object to no data
	 */
	protected function initialize() {
		$this->_created = time();
		$this->_load = true;
		$this->_dirty = true;
		$this->_data = array();
	}
	public static function find($name) {
		self::init_instance();
		if (call_user_func_array(array(
			self::$instance_class,
			"static_exists"
		), array(
			$name
		))) {
			return self::register($name);
		}
		return null;
	}
	private static function static_call($func) {
		self::init_instance();
		if (method_exists(self::$instance_class, $func)) {
			return call_user_func_array(array(
				self::$instance_class,
				$func
			), array());
		}
		return null;
	}
	public static function invalidate($name) {
		$cache = self::register($name);
		$cache->delete();
	}
	/**
	 * Register (create) a Cache object.
	 * Identically named cache objects are always singletons, so:
	 * <code>
	 * $a = Cache::register("A");
	 * $b = Cache::register("A");
	 * </code>
	 * Are, in fact, the same object.
	 * 
	 * @param string $name
	 *        	This cache's name
	 * @return Cache
	 */
	public static function register($name) {
		if (is_array($name)) {
			$name = implode("-", $name);
		}
		assert('is_string($name)');
		// Blow away cached data when directory path changes (due to reconfiguration)
		self::static_call('static_preregister');
		$cache = avalue(self::$caches, $name);
		if (!$cache instanceof Cache) {
			if (count(self::$caches) === 0) {
				self::static_call('static_initialize');
			}
			self::$caches[$name] = $cache = Cache::instance($name);
		}
		//		log::debug("Memory usage Cache::register($name)");
		return self::$caches[$name];
	}
	final public function erase() {
		$this->_load = false;
		$this->_dirty = true;
		$this->_data = array();
		return $this;
	}
	
	/**
	 * Load the cache file if it exists
	 * 
	 * @return void
	 */
	final protected function load() {
		//		$start = microtime(true);
		if (($data = $this->fetch()) !== null) {
			//			zesk::profile_timer("Cache::load(" . $this->_name . ")", microtime(true) - $start);
			if (!is_array($data)) {
				$this->_data = array();
			} else if (!array_key_exists('*version', $data)) {
				$this->_data = $data;
			} else {
				$this->_data = $data['data'];
				$this->_internal = $data['internal'];
				$this->_version = $data['*version'];
				$this->_created = $this->_created;
			}
		} else {
			$this->_data = array();
		}
		$this->_load = false;
		$this->_dirty = false;
	}
	/**
	 * Write the cache file to disk if necessary
	 * 
	 * @return void
	 */
	final public function flush() {
		if (self::$disabled) {
			return;
		}
		if ($this->_dirty) {
			if (count($this->_data) === 0) {
				$this->delete();
			} else if (!$this->store(array(
				'*version' => self::version,
				'data' => $this->_data,
				'internal' => $this->_internal
			))) {
				log::error("Cache::flush: Can't write $this->_name");
			}
		}
	}
	
	/**
	 * Invalidate this cache object when an object changes
	 *
	 * Cron assists with cleaning out these objects in the background.
	 *
	 * @param Object $object        	
	 * @param unknown $member_names        	
	 * @return Cache
	 */
	final public function invalidate_changed(Object $object, $member_names) {
		if ($this->_load) {
			$this->load();
		}
		$member_names = to_list($member_names);
		sort($member_names);
		$members = arr::flatten($object->members($member_names));
		$class = get_class($object);
		$id = $object->id();
		$args = apath($this->_internal, array(
			'check_invalidate_changed',
			$class,
			$id
		));
		if (is_array($args)) {
			if ($args['member_names'] !== $member_names) {
				$this->erase();
			} else if ($members !== $args['original']) {
				$this->erase();
			} else {
				return $this;
			}
		}
		$this->_internal['check_invalidate_changed'][$class][$id] = array(
			'member_names' => $member_names,
			'original' => $members
		);
		$this->_dirty = true;
		return $this;
	}
	final public function invalidate_table_changed($class) {
		if ($this->_load) {
			$this->load();
		}
		$class = strtolower($class);
		$value = apath($this->_internal, 'check_table_changed', $class);
		if (!$value) {
			/* @var $class_object Class_Object */
			$class_object = Object::cache_class($class, 'class');
			if (!$class_object) {
				throw new Exception_Class_NotFound($class, "No Class cache found");
			}
			$this->_internal['check_table_changed'][$class] = array(
				'time' => time(),
				'table' => $class_object->table,
				'database' => $class_object->database_name,
				'table_info' => $class_object->database()->table_info($class_object->table)
			);
			$this->_dirty = true;
		}
		return $this;
	}
	
	/**
	 * Dump the cache file to standard out
	 * 
	 * @return void
	 * @see dump
	 */
	final public function dump() {
		$this->load();
		dump($this->_data);
	}
	
	/**
	 * Retrieve just certain keys from the cache object
	 *
	 * @param string $list        	
	 * @return array
	 */
	final public function filter($list = null) {
		if ($list === null) {
			return $this->_data;
		}
		return arr::filter($this->_data, $list);
	}
	
	/**
	 * Override of PHP 5 built-in "get" accessor.
	 * Enables the following:
	 * <code>
	 * $a = Cache::register("foo");
	 * echo $a->Value;
	 * </code>
	 * 
	 * @param string $x
	 *        	Name of value to get
	 * @return mixed null if value doesn't exist
	 */
	final function __get($x) {
		if ($this->_load) {
			$this->load();
		}
		$x = strval($x);
		return isset($this->_data[$x]) ? $this->_data[$x] : null;
	}
	final function __isset($x) {
		if ($this->_load) {
			$this->load();
		}
		return array_key_exists(strval($x), $this->_data);
	}
	/**
	 * Override of PHP 5 built-in "set" function.
	 * Enables the following:
	 * <code>
	 * $a = Cache::register("foo");
	 * $a->Value = "Hello, world!";
	 * </code>
	 * 
	 * @param string $x
	 *        	A value name to set
	 * @param mixed $v
	 *        	The value to set
	 * @return void
	 */
	final function __set($x, $v) {
		if ($this->_load) {
			$this->load();
		}
		$x = strval($x);
		if (isset($this->_data[$x]) && ($v === $this->_data[$x])) {
			return;
		}
		$this->_data[$x] = $v;
		$this->_dirty = true;
	}
	final function has($x) {
		if ($this->_load) {
			$this->load();
		}
		return array_key_exists($x, $this->_data);
	}
	final function get($x = null, $default = null) {
		if ($this->_load) {
			$this->load();
		}
		if ($x === null) {
			return $this->_data;
		}
		return avalue($this->_data, strval($x), $default);
	}
	final function path_get($path = null, $default = null) {
		if ($this->_load) {
			$this->load();
		}
		if ($path === null) {
			return $this->_data;
		}
		return apath($this->_data, $path, $default);
	}
	final function path_set($path, $value = null) {
		if ($this->_load) {
			$this->load();
		}
		arr::path_set($this->_data, $path, $value);
		$this->_dirty = true;
		return $this;
	}
	
	/**
	 * Set a named value in this cache
	 *
	 * @param string $name
	 *        	Name of cache key
	 * @param mixed $value
	 *        	Value to store
	 * @return Cache
	 */
	final function set($name, $value) {
		$this->__set($name, $value);
		return $this;
	}
	
	/**
	 * Called when class is loaded
	 */
	final static public function hooks(Kernel $zesk) {
		$zesk->configuration->deprecated('Cache::interface', __CLASS__ . "::interface");
		$zesk->configuration->pave(__CLASS__);
		$zesk->hooks->add('reset', __CLASS__ . "::reset");
		$zesk->hooks->add('exit', __CLASS__ . "::at_exit");
	}
	
	/**
	 * Cache shutdown function, called at end of request after all data has been sent to client
	 * Flushes all Cache objects to save them to disk
	 */
	final static public function at_exit() {
		self::static_call("static_exit");
		/* @var $cache Cache */
		foreach (self::$caches as $cache) {
			$cache->flush();
		}
	}
	
	/**
	 * Cache shutdown function, called at end of request after all data has been sent to client
	 * Flushes all Cache objects to save them to disk
	 */
	final static public function reset() {
		self::$caches = array();
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	final public function offsetExists($offset) {
		return $this->has($offset);
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	final public function offsetGet($offset) {
		return $this->get($offset);
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 * @param
	 *        	value
	 */
	final public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}
	
	/**
	 *
	 * @param
	 *        	offset
	 */
	final public function offsetUnset($offset) {
		$this->set($offset, null);
	}
}


