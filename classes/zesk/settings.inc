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
	 *
	 * @var Settings
	 */
	private static $instance = null;
	
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
		$class = $zesk->configuration->settings->get("instance_class", "Settings");
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
		$zesk->configuration->pave("Settings");
		$hooks = $zesk->hooks;
		$hooks->add('zesk\Application::configured', __CLASS__ . '::configured', 'first');
		$hooks->add(Hooks::hook_reset, 'Settings::reset');
		$hooks->add(Hooks::hook_exit, 'Settings::flush_instance');
	}
	
	/**
	 * Reset settings
	 */
	public static function reset() {
		global $zesk;
		/* @var $zesk Kernel */
		self::$db_down = null;
		self::$db_down_why = null;
		self::$changes = array();
		$zesk->objects->session = null;
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
	 * configured Hook
	 */
	public static function configured(Application $application) {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		// If no databases registered, don't bother loading.
		if (count(Database::register()) === 0) {
			$zesk->logger->notice("{method} - no databases, not loading configuration", array(
				"method" => __METHOD__
			));
			return;
		}
		$fix_bad_globals = $zesk->configuration->path_get_first(array(
			__CLASS__ . "::fix_bad_globals",
			"Settings::fix_bad_globals"
		), false);
		$exception = null;
		try {
			$cache = self::_cache();
			if (!$cache->has('globals')) {
				//zesk()->logger->debug("Settings::configured() does not have globals .. loading");
				$globals = array();
				$n_loaded = 0;
				$size_loaded = 0;
				foreach ($application->query_select("Settings")->to_array("name", "value") as $name => $value) {
					++$n_loaded;
					$size_loaded += strlen($value);
					if (is_string($value)) {
						try {
							$globals[$name] = $value = self::unserialize($value);
						} catch (Exception_Syntax $e) {
							if ($fix_bad_globals) {
								$zesk->logger->warning("Bad global {name} can not be unserialized - DELETING", array(
									"name" => $name
								));
								$application->query_delete("Settings")->where("name", $name)->execute();
							} else {
								$zesk->logger->error("Bad global {name} can not be unserialized, please fix manually", array(
									"name" => $name
								));
							}
						}
					}
				}
				$zesk->logger->debug("{method} - loaded {n} globals {size} of data", array(
					"method" => __METHOD__,
					"n" => $n_loaded,
					"size" => \number::format_bytes($size_loaded)
				));
				$cache->globals = $application->call_hook_arguments("filter_settings", array(
					$globals
				), $globals);
			} else {
				//zesk()->logger->debug("Settings::configured() has globals {globals}", array("globals" => $cache->globals));
			}
			$n_loaded = 0;
			foreach ($cache->globals as $key => $value) {
				++$n_loaded;
				$zesk->configuration->pave_set($key, $value);
			}
			$zesk->logger->debug("{method} - loaded {n} globals from cache", array(
				"method" => __METHOD__,
				"n" => $n_loaded
			));
			self::$changes = array();
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
			zesk()->logger->debug("Settings::flush: Database is down, can not save changes {changes} because of {e}", array(
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
		foreach (self::$changes as $name => $value) {
			$settings = new Settings(array(
				'name' => $name
			));
			if ($value === null) {
				$settings->delete();
			} else {
				$settings->set_member('value', $value);
				$settings->store();
			}
		}
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
	 * Global to save
	 *
	 * @see Object::__set($member, $value)
	 */
	public function __set($name, $value) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		self::$changes[zesk_global_key_normalize($name)] = $value;
		$zesk->configuration->pave_set($name, $value);
	}
	
	/**
	 * Global to save
	 *
	 * @see Object::set($member, $value)
	 * @return Settings
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
			$value = $this->application->query_select('Settings')
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
}
