<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Cache\CacheItemInterface;

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see Class_Settings
 */
class Settings extends ORM implements Interface_Data, Interface_Settings {
	/**
	 * Default cache expiration
	 *
	 * @var integer
	 */
	public const SETTINGS_CACHE_EXPIRE_AFTER = 60;

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
	private $changes = [];

	/**
	 *
	 * @param Application $application
	 * @return \zesk\Interface_Settings
	 * @throws Exception_Configuration
	 */
	public static function singleton(Application $application) {
		if ($application->objects->settings instanceof Interface_Settings) {
			return $application->objects->settings;
		}
		$class = $application->configuration->path_get(__CLASS__ . '::instance_class', __CLASS__);
		$settings = $application->objects->factory($class, $application);
		if (!$settings instanceof Interface_Settings) {
			throw new Exception_Configuration(__CLASS__ . '::instance_class', 'Must be Interface_Settings, class is {class}', [
				'class' => $class,
			]);
		}
		$application->hooks->add(Hooks::HOOK_EXIT, [
			$settings,
			'flush_instance',
		]);
		return $application->objects->settings = $settings;
	}

	/**
	 * Hook ORM::hooks
	 */
	public static function hooks(Application $application): void {
		$hooks = $application->hooks;
		// Ensure Database gets a chance to register first
		$hooks->register_class(Database::class);
		$hooks->add('configured', __CLASS__ . '::configured', ['first' => true]);
		$hooks->add(Hooks::HOOK_RESET, function () use ($application): void {
			$application->objects->settings = null;
		});

		$application->configuration->path(__CLASS__);
	}

	/**
	 * Cache for the settings
	 *
	 * @return CacheItemInterface
	 */
	private static function _cache_item(Application $application, CacheItemInterface $item = null) {
		if ($item) {
			$expires = $application->configuration->path_get([
				__CLASS__,
				'cache_expire_after',
			], self::SETTINGS_CACHE_EXPIRE_AFTER);
			if ($expires) {
				$item->expiresAfter($expires);
			}
			$application->cache->saveDeferred($item);
			return $item;
		}
		return $application->cache->getItem(self::CACHE_ITEM_KEY);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $serialized
	 * @return mixed|null
	 * @throws Exception_Syntax
	 */
	private static function unserialize(Application $application, $serialized) {
		try {
			$value = @unserialize($serialized);
			if ($value === false && $serialized !== 'b:0;') {
				throw new Exception_Syntax('Serialized value has an error');
			}
			return $value;
		} catch (Exception_Class_NotFound $e) {
			$application->hooks->call('exception', $e);
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
		$globals = [];
		$size_loaded = 0;
		$n_loaded = 0;
		$object = $application->orm_registry(__CLASS__);
		$fix_bad_globals = $object->optionBool('fix_bad_globals');

		foreach ($object->query_select()->to_array('name', 'value') as $name => $value) {
			++$n_loaded;
			$size_loaded += strlen($value);
			if (is_string($value)) {
				try {
					$globals[$name] = $value = self::unserialize($application, $value);
					if ($debug_load) {
						$application->logger->debug('{method} Loaded {name}={value}', [
							'method' => __METHOD__,
							'name' => $name,
							'value' => $value,
						]);
					}
				} catch (Exception_Syntax $e) {
					if ($fix_bad_globals) {
						$application->logger->warning('{method}: Bad global {name} can not be unserialized - DELETING', [
							'method' => __METHOD__,
							'name' => $name,
						]);
						$application->orm_registry(__CLASS__)->query_delete()->where('name', $name)->execute();
					} else {
						$application->logger->error('{method}: Bad global {name} can not be unserialized, please fix manually', [
							'method' => __METHOD__,
							'name' => $name,
						]);
					}
				}
			}
		}
		if ($debug_load) {
			$application->logger->debug('{method} - loaded {n} globals {size} of data', [
				'method' => __METHOD__,
				'n' => $n_loaded,
				'size' => Number::format_bytes($application->locale, $size_loaded),
			]);
		}
		$globals = $application->call_hook_arguments('filter_settings', [
			$globals,
		], $globals);
		return $globals;
	}

	/**
	 * configured Hook
	 */
	public static function configured(Application $application): void {
		$__ = [
			'method' => __METHOD__,
		];
		$debug_load = $application->configuration->path_get([
			__CLASS__,
			'debug_load',
		]);
		if ($debug_load) {
			$application->logger->debug('{method} entry', $__);
		}
		// If no databases registered, don't bother loading.
		$databases = $application->database_module()->register();
		if (count($databases) === 0) {
			if ($debug_load) {
				$application->logger->debug('{method} - no databases, not loading configuration', $__);
			}
			return;
		}
		$application->configuration->deprecated('Settings', __CLASS__);
		$settings = self::singleton($application);
		$cache_disabled = $settings->optionBool('cache_disabled');
		$exception = null;

		try {
			if ($cache_disabled) {
				if ($debug_load) {
					$application->logger->debug('{method} cache disabled', $__);
				}
				$globals = self::load_globals_from_database($application, $debug_load);
			} else {
				$cache = self::_cache_item($application);
				if (!$cache->isHit()) {
					if ($debug_load) {
						$application->logger->debug('{method} does not have cached globals .. loading', $__);
					}
					$globals = self::load_globals_from_database($application, $debug_load);
					$cache->set($globals);
					self::_cache_item($application, $cache);
				} else {
					if ($debug_load) {
						$application->logger->debug('{method} - loading globals from cache', $__);
					}
					$globals = $cache->get();
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
		} catch (Database_Exception_Database_NotFound $e) {
			$exception = $e;
		} catch (Exception_Semantics $e) {
			// Columns may have changed
			$exception = $e;
		} catch (Exception_Configuration $e) {
			// App is not configured
		} catch (\Exception $e) {
			// Database is misconfigured/misnamed
			$exception = $e;
		}
		if ($exception) {
			$application->hooks->call('exception', $exception);
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
	public function flush_instance($force = false): void {
		if (count($this->changes) === 0) {
			return;
		}
		if ($this->db_down && !$force) {
			$this->application->logger->debug('{method}: Database is down, can not save changes {changes} because of {e}', [
				'method' => __METHOD__,
				'class' => __CLASS__,
				'changes' => $this->changes,
				'e' => $this->db_down_why,
			]);
			return;
		}
		$this->db_down = false;
		$this->flush();
	}

	public const CACHE_ITEM_KEY = __CLASS__;

	/**
	 * Internal function to write all settings store in this object to the database instantly.
	 */
	public function flush(): void {
		$debug_save = $this->optionBool('debug_save');
		foreach ($this->changes as $name => $value) {
			$settings = $this->application->orm_factory(__CLASS__, [
				'name' => $name,
			]);
			if ($value === null) {
				$settings->delete();
				if ($debug_save) {
					$settings->application->logger->debug('Deleting {class} {name}', [
						'class' => get_class($settings),
						'name' => $name,
					]);
				}
			} else {
				$settings->set_member('value', $value);
				$settings->store();
				if ($debug_save) {
					$settings->application->logger->debug('Saved {class} {name}={value}', [
						'class' => get_class($settings),
						'name' => $name,
						'value' => $value,
					]);
				}
			}
		}
		$this->application->logger->debug('Deleted {class} cache', [
			'class' => __CLASS__,
		]);
		$this->application->cache->deleteItem(self::CACHE_ITEM_KEY);
		$this->changes = [];
	}

	/**
	 * Override get to retrieve from global state
	 *
	 * @param $name Setting
	 *            to retrieve
	 * @return mixed
	 */
	public function __get($name): mixed {
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
	public function __isset($member): bool {
		return $this->application->configuration->path_exists($member);
	}

	/**
	 * Global to save
	 *
	 * @see ORM::__set($member, $value)
	 */
	public function __set($name, $value): void {
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
	 * @return self
	 * @see ORM::set($member, $value)
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
			$value = $this->application->orm_registry(__CLASS__)->query_select()->where('name', $name)->addWhat('value', 'value')->one('value');
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
		$update = $this->application->orm_registry(Settings::class)->query_update();
		$old_prefix_quoted = $update->sql()->quote_text($old_prefix);
		$old_prefix_like_quoted = tr($old_prefix, [
			'\\' => '\\\\',
			'_' => '\\_',
		]);
		$nrows = $update->value('*name', "REPLACE(name, $old_prefix_quoted, " . $update->database()->quote_text(strtolower($new_prefix)) . ')')->where('name|LIKE', "$old_prefix_like_quoted%")->execute()->affected_rows();
		if ($nrows > 0) {
			$this->application->logger->notice('Updated {nrows} settings from {old_prefix} to use new prefix {new_prefix}', [
				'nrows' => $nrows,
				'old_prefix' => $old_prefix,
				'new_prefix' => $new_prefix,
			]);
		}
		return $nrows;
	}
}
