<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use zesk\Application;
use zesk\Database;
use zesk\Database_Exception_Connect;
use zesk\Database_Exception_Database_NotFound;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Database_Exception_Unknown_Schema;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception as BaseException;
use zesk\Hooks;
use zesk\Interface_Data;
use zesk\Interface_Settings;
use zesk\Number;

/**
 * Base class for global settings to be retrieved/stored from permanent storage
 *
 * @author kent
 * @see Class_Settings
 */
class Settings extends ORMBase implements Interface_Data, Interface_Settings {
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
	private bool $db_down = false;

	/**
	 * Reason why the database is down
	 *
	 * @var BaseException|null
	 */
	private BaseException|null $db_down_why = null;

	/**
	 * List of global changes to settings to be saved
	 *
	 * @var array
	 */
	private array $changes = [];

	/**
	 *
	 * @param Application $application
	 * @return Interface_Settings
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Semantics
	 */
	public static function singleton(Application $application): Interface_Settings {
		return $application->settings();
//		if ($application->objects->settings instanceof Interface_Settings) {
//			return $application->objects->settings;
//		}
//		$class = $application->configuration->getPath(__CLASS__ . '::instance_class', __CLASS__);
//		$settings = $application->objects->factory($class, $application);
//		if (!$settings instanceof Interface_Settings) {
//			throw new Exception_Configuration(__CLASS__ . '::instance_class', 'Must be Interface_Settings, class is {class}', [
//				'class' => $class,
//			]);
//		}
//		$application->hooks->add(Hooks::HOOK_EXIT, [
//			$settings,
//			'flush_instance',
//		]);
//		return $application->objects->settings = $settings;
	}

	/**
	 * Hook ORM::hooks
	 */
	public static function hooks(Application $application): void {
		$hooks = $application->hooks;
		// Ensure Database gets a chance to register first
		$hooks->registerClass(Database::class);
		$hooks->add('configured', self::configured(...), ['first' => true]);
		$application->configuration->path(__CLASS__);
	}

	/**
	 * Get cache for the settings
	 *
	 * @param Application $application
	 * @return CacheItemInterface
	 * @throws InvalidArgumentException
	 */
	private static function _getCacheItem(Application $application): CacheItemInterface {
		return $application->cache->getItem(self::CACHE_ITEM_KEY);
	}

	/**
	 * Set cache for the settings
	 *
	 * @param Application $application
	 * @param CacheItemInterface $item
	 * @return void
	 */
	private static function _setCacheItem(Application $application, CacheItemInterface $item): void {
		$expires = $application->configuration->getPath([
			__CLASS__,
			'cache_expire_after',
		], self::SETTINGS_CACHE_EXPIRE_AFTER);
		if ($expires) {
			$item->expiresAfter($expires);
		}
		$application->cache->saveDeferred($item);
	}

	/**
	 *
	 * @param Application $application
	 * @param string $serialized
	 * @return mixed|null
	 * @throws Exception_Syntax
	 */
	private static function unserialize(Application $application, string $serialized): mixed {
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
	 * @param boolean $debug_load
	 * @return array
	 */
	private static function load_globals_from_database(Application $application, bool $debug_load = false): array {
		$globals = [];
		$size_loaded = 0;
		$n_loaded = 0;
		$object = $application->ormRegistry(__CLASS__);
		$fix_bad_globals = $object->optionBool('fix_bad_globals');

		foreach ($object->querySelect()->toArray('name', 'value') as $name => $value) {
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
						$application->ormRegistry(__CLASS__)->query_delete()->addWhere('name', $name)->execute();
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
		$globals = $application->callHookArguments('filter_settings', [
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
		$debug_load = $application->configuration->getPath([
			__CLASS__,
			'debug_load',
		]);
		if ($debug_load) {
			$application->logger->debug('{method} entry', $__);
		}
		// If no databases registered, don't bother loading.
		$databases = $application->database_module()->databases();
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
				$cache = self::_getCacheItem($application);
				if (!$cache->isHit()) {
					if ($debug_load) {
						$application->logger->debug('{method} does not have cached globals .. loading', $__);
					}
					$globals = self::load_globals_from_database($application, $debug_load);
					$cache->set($globals);
					self::_setCacheItem($application, $cache);
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
				$application->configuration->setPath($key, $value);
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
			$settings = $this->application->ormFactory(__CLASS__, [
				'name' => $name,
			]);
			if ($value === null) {
				$settings->delete();
				if ($debug_save) {
					$settings->application->logger->debug('Deleting {class} {name}', [
						'class' => $settings::class,
						'name' => $name,
					]);
				}
			} else {
				$settings->setMember('value', $value);
				$settings->store();
				if ($debug_save) {
					$settings->application->logger->debug('Saved {class} {name}={value}', [
						'class' => $settings::class,
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
	 * @param string $name Setting to retrieve
	 * @return mixed
	 */
	public function __get(string $name): mixed {
		return $this->application->configuration->getPath($name);
	}

	/**
	 * Same as __get with a default
	 *
	 * @see ORMBase::get($mixed, $default)
	 */
	public function get(string $name, mixed $default = null): mixed {
		return $this->application->configuration->getPath($name, $default);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Model::__isset()
	 */
	public function __isset($member): bool {
		return $this->application->configuration->pathExists($member);
	}

	/**
	 * Global to save
	 *
	 * @see ORMBase::__set($member, $value)
	 */
	public function __set(string $key, mixed $value): void {
		$old_value = $this->application->configuration->getPath($key);
		if ($old_value === $value) {
			return;
		}

		$this->changes[$this->application->configuration->normalizeKey($key)] = $value;
		$this->application->configuration->setPath($key, $value);
	}

	/**
	 * Global to save
	 *
	 * @return self
	 * @see ORMBase::set($member, $value)
	 */
	public function set(string $name, mixed $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	/**
	 *
	 */
	/**
	 * @param string $name
	 * @return mixed
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 */
	public function data(string $name): mixed {
		$value = $this->application->ormRegistry(__CLASS__)->querySelect()->addWhere('name', $name)->addWhat('value', 'value')->one('value');
		if ($value === null) {
			return null;
		}
		return unserialize($value);
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setData(string $name, mixed $value): self {
		$this->__set($name, $value);
		$this->flush();
		return $this;
	}

	/**
	 *
	 * @see Interface_Data::deleteData()
	 * @param array|string $name
	 * @return $this
	 */
	public function deleteData(array|string $name): self {
		foreach (toArray($name) as $item) {
			$this->__set($item, null);
		}
		$this->flush();
		return $this;
	}

	/**
	 *
	 * @see Interface_Data::delete_data()
	 * @param $name
	 * @return $this
	 */
	public function delete_data(array|string $name): self {
		$this->application->deprecated(__METHOD__);
		return $this->deleteData($name);
	}

	/**
	 * Call this when you change your setting names
	 *
	 * @param string $old_setting
	 * @param string $new_setting
	 * @return $this|void
	 * @throws Exception_Deprecated
	 */
	public function deprecated(string $old_setting, string $new_setting) {
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
	public function prefixUpdated(string $old_prefix, string $new_prefix): int {
		$update = $this->application->ormRegistry(Settings::class)->queryUpdate();
		$old_prefix_quoted = $update->sql()->quoteText($old_prefix);
		$old_prefix_like_quoted = tr($old_prefix, [
			'\\' => '\\\\',
			'_' => '\\_',
		]);
		$rowCount = $update->value('*name', "REPLACE(name, $old_prefix_quoted, " . $update->database()->quoteText(strtolower($new_prefix)) . ')')->addWhere('name|LIKE', "$old_prefix_like_quoted%")->execute()->affectedRows();
		if ($rowCount > 0) {
			$this->application->logger->notice('Updated {rowCount} settings from {old_prefix} to use new prefix {new_prefix}', [
				'rowCount' => $rowCount,
				'old_prefix' => $old_prefix,
				'new_prefix' => $new_prefix,
			]);
		}
		return $rowCount;
	}
}
