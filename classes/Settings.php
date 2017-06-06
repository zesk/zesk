<?php

/**
 * $URL$
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see Class_Settings
 */
class Settings extends Object implements Interface_Data, Interface_Settings {
	
	/**
	 * Is the database down?
	 *
	 * @var boolean
	 */
	private static $db_down = false;
	
	/**
	 * Reason why the database is down
	 *
	 * @var Exception
	 */
	private static $db_down_why = null;
	
	/**
	 * List of global changes to settings to be saved
	 *
	 * @var string
	 */
	static $changes = array();
	
	/**
	 * Retrieve the Settings singleton.
	 *
	 * @return NULL|Settings
	 */
	public static function instance() {
		global $zesk;
		
		/* @var $zesk Kernel */
		if ($zesk->objects->settings instanceof Interface_Settings) {
			return $zesk->objects->settings;
		}
		$class = $zesk->configuration->path_get(__CLASS__ . "::instance_class", __CLASS__);
		$zesk->objects->settings = $zesk->objects->factory($class);
		if (!$zesk->objects->settings instanceof Interface_Settings) {
			throw new Exception_Configuration(__CLASS__ . "::instance_class", "Must be Interface_Settings, class is {class}", array(
				"class" => $class
			));
		}
		return $zesk->objects->settings;
	}
	
	/**
	 * Hook Object::hooks
	 */
	public static function hooks(Kernel $zesk) {
		// Ensure Database gets a chance to register first
		$zesk->hooks->register_class("zesk\\Database");
		$zesk->configuration->path(__CLASS__);
		$hooks = $zesk->hooks;
		$hooks->add('configured', __CLASS__ . '::configured', 'first');
		$hooks->add(Hooks::hook_reset, __CLASS__ . '::reset');
		$hooks->add(Hooks::hook_exit, __CLASS__ . '::flush_instance');
	}
	
	/**
	 * Reset settings
	 */
	public static function reset() {
		self::$changes = array();
		self::$db_down = null;
		self::$db_down_why = null;
		self::$changes = array();
	}
	/**
	 * Cache for the settings
	 *
	 * @return Cache
	 */
	public static function _cache() {
		return Cache::register(__CLASS__)->expire_after(60);
	}
	private static function unserialize($serialized) {
		try {
			$value = @unserialize($serialized);
			if ($value === false && $serialized !== 'b:0;') {
				throw new Exception_Syntax("Serialized value has an error");
			}
			return $value;
		} catch (Exception_Class_NotFound $e) {
			zesk()->hooks->call("exception", $e);
			return null;
		}
	}
	
	/**
	 * 
	 * @param Application $application
	 * @param boolean $fix_bad_globals
	 * @return array
	 */
	private static function load_globals_from_database(Application $application) {
		$globals = array();
		$size_loaded = 0;
		$n_loaded = 0;
		$object = $application->object(__CLASS__);
		$debug_load = $application->configuration->path_get(array(
			__CLASS__,
			"debug_load"
		));
		$fix_bad_globals = $object->option_bool("fix_bad_globals");
		
		foreach ($application->query_select(__CLASS__)->to_array("name", "value") as $name => $value) {
			++$n_loaded;
			$size_loaded += strlen($value);
			if (is_string($value)) {
				try {
					$globals[$name] = $value = self::unserialize($value);
					if ($debug_load) {
						$application->logger->debug("{method} Loaded {name}={value}", array(
							"method" => __METHOD__,
							"name" => $name,
							"value" => $value
						));
					}
				} catch (Exception_Syntax $e) {
					if ($fix_bad_globals) {
						$application->logger->warning("{method}: Bad global {name} can not be unserialized - DELETING", array(
							"method" => __METHOD__,
							"name" => $name
						));
						$application->query_delete(__CLASS__)->where("name", $name)->execute();
					} else {
						$application->logger->error("{method}: Bad global {name} can not be unserialized, please fix manually", array(
							"method" => __METHOD__,
							"name" => $name
						));
					}
				}
			}
		}
		$application->logger->debug("{method} - loaded {n} globals {size} of data", array(
			"method" => __METHOD__,
			"n" => $n_loaded,
			"size" => Number::format_bytes($size_loaded)
		));
		$globals = $application->call_hook_arguments("filter_settings", array(
			$globals
		), $globals);
		return $globals;
	}
	
	/**
	 * configured Hook
	 */
	public static function configured(Application $application) {
		$__ = array(
			"method" => __METHOD__
		);
		$application->logger->debug("{method} entry", $__);
		// If no databases registered, don't bother loading.
		if (count(Database::register()) === 0) {
			$application->logger->debug("{method} - no databases, not loading configuration", $__);
			return;
		}
		$application->configuration->deprecated("Settings", __CLASS__);
		$object = $application->object(__CLASS__);
		$object->inherit_global_options();
		$cache_disabled = $object->option_bool("cache_disabled");
		$exception = null;
		try {
			if ($cache_disabled) {
				$application->logger->debug("{method} cache disabled", $__);
				$globals = self::load_globals_from_database($application);
			} else {
				$cache = self::_cache();
				if (!$cache->has('globals')) {
					$application->logger->debug("{method} does not have cached globals .. loading", $__);
					$globals = self::load_globals_from_database($application);
					$cache->globals = $globals;
				} else {
					$application->logger->debug("{method} - loading globals from cache", $__);
					$globals = $cache->globals;
				}
			}
			$n_loaded = 0;
			foreach ($globals as $key => $value) {
				++$n_loaded;
				$application->configuration->path_set($key, $value);
			}
		} catch (Database_Exception_Table_NotFound $e) {
			$exception = $e;
		} catch (Database_Exception_Connect $e) {
			$exception = $e;
		} catch (Database_Exception_Unknown_Schema $e) {
			$exception = $e;
		} catch (Exception_Semantics $e) {
			// Columns may have changed
			$exception = $e;
		}
		if ($exception) {
			$application->hooks->call("exception", $exception);
			self::$db_down = true;
			self::$db_down_why = $exception;
		}
	}
	
	/**
	 * Hook shutdown - save all settings to database
	 */
	public static function flush_instance($force = false) {
		if (count(self::$changes) === 0) {
			return;
		}
		if (self::$db_down && !$force) {
			zesk()->logger->debug("{method}: Database is down, can not save changes {changes} because of {e}", array(
				"method" => __METHOD__,
				"class" => __CLASS__,
				"changes" => self::$changes,
				"e" => self::$db_down_why
			));
			return;
		}
		self::$db_down = false;
		self::instance()->flush();
	}
	
	/**
	 * Internal function to write all settings to the database
	 */
	public function flush() {
		$debug_save = $this->option_bool("debug_save");
		foreach (self::$changes as $name => $value) {
			$settings = $this->application->object_factory(__CLASS__, array(
				'name' => $name
			));
			if ($value === null) {
				$settings->delete();
				if ($debug_save) {
					$settings->application->logger->debug("Deleting {class} {name}", array(
						"class" => get_class($settings),
						"name" => $name
					));
				}
			} else {
				$settings->set_member('value', $value);
				$settings->store();
				if ($debug_save) {
					$settings->application->logger->debug("Saved {class} {name}={value}", array(
						"class" => get_class($settings),
						"name" => $name,
						"value" => $value
					));
				}
			}
		}
		$this->application->logger->debug("Deleted {class} cache", array(
			"class" => __CLASS__
		));
		self::_cache()->delete();
		self::$changes = array();
	}
	
	/**
	 * Override get to retrieve from global state
	 *
	 * @param $name Setting
	 *        	to retrieve
	 * @return mixed
	 */
	public function __get($name) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->path_get($name);
	}
	
	/**
	 * Same as __get with a default
	 *
	 * @see Object::get($mixed, $default)
	 */
	public function get($name = null, $default = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->path_get($name, $default);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see Model::__isset()
	 */
	public function __isset($member) {
		return zesk()->configuration->path_exists($member);
	}
	
	/**
	 * Global to save
	 *
	 * @see Object::__set($member, $value)
	 */
	public function __set($name, $value) {
		global $zesk;
		$old_value = $zesk->configuration->path_get($name);
		if ($old_value === $value) {
			return;
		}
		/* @var $zesk zesk\Kernel */
		self::$changes[zesk_global_key_normalize($name)] = $value;
		$zesk->configuration->path_set($name, $value);
	}
	
	/**
	 * Global to save
	 *
	 * @see Object::set($member, $value)
	 * @return self
	 */
	public function set($name, $value = null) {
		$this->__set($name, $value);
		return $this;
	}
	
	/**
	 *
	 * @see Interface_Data::data()
	 */
	public function data($name, $value = null) {
		if ($value === null) {
			$value = $this->application->query_select(__CLASS__)
				->where("name", $name)
				->what("value", "value")
				->one("value");
			if ($value === null) {
				return null;
			}
			return unserialize($value);
		}
		$this->__set($name, $value);
		$this->flush();
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Interface_Data::delete_data()
	 */
	public function delete_data($name) {
		$names = to_list($name);
		foreach ($names as $name) {
			$this->__set($name, null);
		}
		$this->flush();
		return $this;
	}
	
	/**
	 * Call this when you change your setting names
	 * 
	 * @param unknown $old_setting
	 * @param unknown $new_setting
	 */
	public function deprecated($old_setting, $new_setting) {
		if (!$this->__isset($old_setting)) {
			return;
		}
		zesk()->deprecated(__CLASS__ . "::deprecated(\"$old_setting\", \"$new_setting\")");
		if ($this->__isset($new_setting)) {
			$this->__set($old_setting, null);
			return $this;
		}
		$this->__set($new_setting, $this->__get($old_setting));
		$this->__set($old_setting, null);
		return $this;
	}
}
