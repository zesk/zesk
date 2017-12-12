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
class Settings extends ORM implements Interface_Data, Interface_Settings {
	
	/**
	 * Is the database down?
	 *
	 * @var boolean
	 */
	private $db_down = false;
	
	/**
	 * Reason why the database is down
	 *
	 * @var Exception
	 */
	private $db_down_why = null;
	
	/**
	 * List of global changes to settings to be saved
	 *
	 * @var string
	 */
	private $changes = array();
	
	/**
	 * Retrieve the Settings singleton.
	 *
	 * @deprecated 2017-08
	 * @see self::singleton
	 * @return NULL|Settings
	 */
	public static function instance() {
		return self::singleton(app());
	}
	
	/**
	 *
	 * @param Application $application        	
	 * @throws Exception_Configuration
	 * @return \zesk\Interface_Settings
	 */
	public static function singleton(Application $application) {
		if ($application->objects->settings instanceof Interface_Settings) {
			return $application->objects->settings;
		}
		$class = $application->configuration->path_get(__CLASS__ . "::instance_class", __CLASS__);
		$settings = $application->objects->factory($class, $application);
		if (!$settings instanceof Interface_Settings) {
			throw new Exception_Configuration(__CLASS__ . "::instance_class", "Must be Interface_Settings, class is {class}", array(
				"class" => $class
			));
		}
		$application->hooks->add(Hooks::hook_exit, array(
			$settings,
			"flush_instance"
		));
		return $application->objects->settings = $settings;
	}
	
	/**
	 * Hook ORM::hooks
	 */
	public static function hooks(Kernel $zesk) {
		// Ensure Database gets a chance to register first
		$zesk->hooks->register_class("zesk\\Database");
		$zesk->configuration->path(__CLASS__);
		$hooks = $zesk->hooks;
		$hooks->add('configured', __CLASS__ . '::configured', 'first');
	}
	
	/**
	 * Cache for the settings
	 *
	 * @return Cache
	 */
	public static function _cache() {
		return Cache::register(__CLASS__)->expire_after(60);
	}
	
	/**
	 *
	 * @param Application $application        	
	 * @param string $serialized        	
	 * @throws Exception_Syntax
	 * @return mixed|null
	 */
	private static function unserialize(Application $application, $serialized) {
		try {
			$value = @unserialize($serialized);
			if ($value === false && $serialized !== 'b:0;') {
				throw new Exception_Syntax("Serialized value has an error");
			}
			return $value;
		} catch (Exception_Class_NotFound $e) {
			$application->hooks->call("exception", $e);
			return null;
		}
	}
	
	/**
	 *
	 * @param Application $application        	
	 * @param boolean $fix_bad_globals        	
	 * @return array
	 */
	private static function load_globals_from_database(Application $application, $debug_load = false) {
		$globals = array();
		$size_loaded = 0;
		$n_loaded = 0;
		$object = $application->object(__CLASS__);
		$fix_bad_globals = $object->option_bool("fix_bad_globals");
		
		foreach ($application->query_select(__CLASS__)->to_array("name", "value") as $name => $value) {
			++$n_loaded;
			$size_loaded += strlen($value);
			if (is_string($value)) {
				try {
					$globals[$name] = $value = self::unserialize($application, $value);
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
		if ($debug_load) {
			$application->logger->debug("{method} - loaded {n} globals {size} of data", array(
				"method" => __METHOD__,
				"n" => $n_loaded,
				"size" => Number::format_bytes($size_loaded)
			));
		}
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
		$debug_load = $application->configuration->path_get(array(
			__CLASS__,
			"debug_load"
		));
		if ($debug_load) {
			$application->logger->debug("{method} entry", $__);
		}
		// If no databases registered, don't bother loading.
		if (count(Database::register()) === 0) {
			if ($debug_load) {
				$application->logger->debug("{method} - no databases, not loading configuration", $__);
			}
			return;
		}
		$application->configuration->deprecated("Settings", __CLASS__);
		$settings = self::singleton($application);
		$cache_disabled = $settings->option_bool("cache_disabled");
		$exception = null;
		try {
			if ($cache_disabled) {
				if ($debug_load) {
					$application->logger->debug("{method} cache disabled", $__);
				}
				$globals = self::load_globals_from_database($application, $debug_load);
			} else {
				$cache = self::_cache();
				if (!$cache->has('globals')) {
					if ($debug_load) {
						$application->logger->debug("{method} does not have cached globals .. loading", $__);
					}
					$globals = self::load_globals_from_database($application, $debug_load);
					$cache->globals = $globals;
				} else {
					if ($debug_load) {
						$application->logger->debug("{method} - loading globals from cache", $__);
					}
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
			$settings->_db_down($exception);
		}
	}
	private function _db_down(\Exception $exception = null) {
		$this->db_down = $exception !== null;
		$this->db_down_why = $exception;
		return $this;
	}
	/**
	 * Hook shutdown - save all settings to database
	 */
	public function flush_instance($force = false) {
		if (count($this->changes) === 0) {
			return;
		}
		if ($this->db_down && !$force) {
			$this->application->logger->debug("{method}: Database is down, can not save changes {changes} because of {e}", array(
				"method" => __METHOD__,
				"class" => __CLASS__,
				"changes" => $this->changes,
				"e" => $this->db_down_why
			));
			return;
		}
		$this->db_down = false;
		$this->flush();
	}
	
	/**
	 * Internal function to write all settings store in this object to the database instantly.
	 */
	public function flush() {
		$debug_save = $this->option_bool("debug_save");
		foreach ($this->changes as $name => $value) {
			$settings = $this->application->orm_factory(__CLASS__, array(
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
		$this->_cache()->delete();
		$this->changes = array();
	}
	
	/**
	 * Override get to retrieve from global state
	 *
	 * @param $name Setting
	 *        	to retrieve
	 * @return mixed
	 */
	public function __get($name) {
		return $this->application->configuration->path_get($name);
	}
	
	/**
	 * Same as __get with a default
	 *
	 * @see ORM::get($mixed, $default)
	 */
	public function get($name = null, $default = null) {
		return $this->application->configuration->path_get($name, $default);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Model::__isset()
	 */
	public function __isset($member) {
		return $this->application->configuration->path_exists($member);
	}
	
	/**
	 * Global to save
	 *
	 * @see ORM::__set($member, $value)
	 */
	public function __set($name, $value) {
		$old_value = $this->application->configuration->path_get($name);
		if ($old_value === $value) {
			return;
		}
		$this->changes[zesk_global_key_normalize($name)] = $value;
		$this->application->configuration->path_set($name, $value);
	}
	
	/**
	 * Global to save
	 *
	 * @see ORM::set($member, $value)
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
		$this->application->deprecated(__CLASS__ . "::deprecated(\"$old_setting\", \"$new_setting\")");
		if ($this->__isset($new_setting)) {
			$this->__set($old_setting, null);
			return $this;
		}
		$this->__set($new_setting, $this->__get($old_setting));
		$this->__set($old_setting, null);
		return $this;
	}
	
	/**
	 * 
	 * @param string $old_prefix
	 * @param string $new_prefix
	 * @return integer
	 */
	public function prefix_updated($old_prefix, $new_prefix) {
		$update = $this->application->query_update(Settings::class);
		$old_prefix_quoted = $update->sql()->quote_text($old_prefix);
		$old_prefix_like_quoted = tr($old_prefix, array(
			"\\" => "\\\\",
			"_" => "\\_"
		));
		$nrows = $update->value("*name", "REPLACE(name, $old_prefix_quoted, " . $update->database()
			->quote_text(strtolower($new_prefix)) . ")")
			->where("name|LIKE", "$old_prefix_like_quoted%")
			->exec()
			->affected_rows();
		if ($nrows > 0) {
			$this->application->logger->notice("Updated {nrows} settings from {old_prefix} to use new prefix {new_prefix}", array(
				"nrows" => $nrows,
				"old_prefix" => $old_prefix,
				"new_prefix" => $new_prefix
			));
		}
		return $nrows;
	}
}
