<?php
/**
 * $URL$
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see Class_Zesk_Settings
 */
class Zesk_Settings extends Object implements Interface_Data {
	
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
		if (self::$instance) {
			return self::$instance;
		}
		$class = zesk::get("Settings::instance_class", "Settings");
		self::$instance = zesk::factory($class);
		if (!self::$instance instanceof Settings) {
			throw new Exception_Configuration("Settings::instance_class", "Must specify subclass of Settings, class is {class}", array(
				"class" => $class
			));
		}
		return self::$instance;
	}
	
	/**
	 * Hook Object::hooks
	 */
	public static function hooks() {
		zesk::add_hook('configured', 'Settings::configured', 'first');
		zesk::add_hook('reset', 'Settings::reset');
		zesk::add_hook('exit', 'Settings::flush_instance');
	}
	
	/**
	 * Reset settings
	 */
	public static function reset() {
		self::$db_down = null;
		self::$db_down_why = null;
		self::$changes = array();
		self::$instance = null;
	}
	/**
	 * Cache for the settings
	 *
	 * @return Cache
	 */
	public static function _cache() {
		return Cache::register(__CLASS__)->expire_after(60);
	}
	private static function unserialize($value) {
		try {
			$value = unserialize($value);
			return $value;
		} catch (Exception_Class_NotFound $e) {
			zesk::hook("exception", $e);
			return null;
		}
	}
	/**
	 * configured Hook
	 */
	public static function configured(Application $application) {
		// If no databases registered, don't bother loading.
		if (count(Database::register()) === 0) {
			return;
		}
		// 		log::debug("{bt}", array(
		// 			"bt" => _backtrace()
		// 		));
		try {
			$cache = self::_cache();
			if (!$cache->has('globals')) {
				//log::debug("Settings::configured() does not have globals .. loading");
				$globals = array();
				foreach (Object::class_query("Settings")->to_array("name", "value") as $name => $value) {
					if (is_string($value)) {
						$globals[$name] = $value = self::unserialize($value);
					}
				}
				$cache->globals = $application->hook_array("filter_settings", array(
					$globals
				), $globals);
			} else {
				//log::debug("Settings::configured() has globals {globals}", array("globals" => $cache->globals));
			}
			zesk::set($cache->globals);
			self::$changes = array();
		} catch (Database_Exception_Table_NotFound $e) {
			zesk::hook("exception", $e);
			self::$db_down = true;
			self::$db_down_why = $e;
			// Ignore for now
		} catch (Database_Exception_Connect $e) {
			zesk::hook("exception", $e);
			self::$db_down = true;
			self::$db_down_why = $e;
			// Ignore for now
		} catch (Exception_Semantics $e) {
			zesk::hook("exception", $e);
			// Columns may have changed
			self::$db_down = true;
			self::$db_down_why = $e;
			// Ignore for now
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
			log::debug("Settings::flush: Database is down, can not save changes {changes} because of {e}", array(
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
		return zesk::get($name);
	}
	
	/**
	 * Same as __get with a default
	 *
	 * @see Object::get($mixed, $default)
	 */
	public function get($name = null, $default = null) {
		return zesk::get($name, $default);
	}
	
	/**
	 * Global to save
	 *
	 * @see Object::__set($member, $value)
	 */
	public function __set($name, $value) {
		self::$changes[implode(ZESK_GLOBAL_KEY_SEPARATOR, _zesk_global_key($name))] = $value;
		zesk::set($name, $value);
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
			$value = Object::class_query('Settings')->where("name", $name)->what("value", "value")->one("value");
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
		$this->__set($name, null);
		$this->flush();
		return $this;
	}
}
