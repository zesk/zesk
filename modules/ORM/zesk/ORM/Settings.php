<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Throwable;
use zesk\Application;
use zesk\CacheItem_NULL;
use zesk\Database;
use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_NoResults;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Convert;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
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
	 */
	public static function singleton(Application $application): Interface_Settings {
		return $application->settings();
	}

	public function hook_initialized(): void {
		$this->application->hooks->add(Hooks::HOOK_EXIT, $this->flush_instance(...));
	}

	/**
	 * Hook ORM::hooks
	 */
	public static function hooks(Application $application): void {
		$hooks = $application->hooks;
		// Ensure Database gets a chance to register first
		$hooks->registerClass(Database::class);
		$hooks->add(Hooks::HOOK_CONFIGURED, self::configured(...), ['first' => true]);
		$application->configuration->path(__CLASS__);
	}

	/**
	 * Get cache for the settings
	 *
	 * @param Application $application
	 * @return CacheItemInterface
	 */
	private static function _getCacheItem(Application $application): CacheItemInterface {
		try {
			return $application->cacheItemPool()->getItem(self::CACHE_ITEM_KEY);
		} catch (InvalidArgumentException $e) {
			return new CacheItem_NULL(self::CACHE_ITEM_KEY);
		}
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
			__CLASS__, 'cache_expire_after',
		], self::SETTINGS_CACHE_EXPIRE_AFTER);
		if ($expires) {
			$item->expiresAfter($expires);
		}
		$application->cacheItemPool()->saveDeferred($item);
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
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Semantics
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
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
							'method' => __METHOD__, 'name' => $name, 'value' => $value,
						]);
					}
				} catch (Exception_Syntax $e) {
					if ($fix_bad_globals) {
						$application->logger->warning('{method}: Bad global {name} can not be unserialized - DELETING', [
							'method' => __METHOD__, 'name' => $name,
						]);
						$application->ormRegistry(__CLASS__)->queryDelete()->addWhere('name', $name)->execute();
					} else {
						$application->logger->error('{method}: Bad global {name} can not be unserialized, please fix manually', [
							'method' => __METHOD__, 'name' => $name,
						]);
					}
				}
			}
		}
		if ($debug_load) {
			$application->logger->debug('{method} - loaded {n} globals {size} of data', [
				'method' => __METHOD__, 'n' => $n_loaded,
				'size' => Number::formatBytes($application->locale, $size_loaded),
			]);
		}
		return $application->callHookArguments('filter_settings', [
			$globals,
		], $globals);
	}

	/**
	 * configured Hook
	 */
	/**
	 * @param Application $application
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws InvalidArgumentException
	 */
	public static function configured(Application $application): void {
		$settings = $application->settings();
		if (!$settings instanceof Settings) {
			$application->logger->debug('{method} Application settings singleton was a {class}, skipping', [
				'method' => __METHOD__,
				'class' => $settings::class,
			]);
			return;
		}
		$__ = [
			'method' => __METHOD__,
		];
		$debug_load = $application->configuration->getPath([
			__CLASS__, 'debug_load',
		]);
		if ($debug_load) {
			$application->logger->debug('{method} entry', $__);
		}
		// If no databases registered, don't bother loading.
		$databases = $application->databaseModule()->databases();
		if (count($databases) === 0) {
			if ($debug_load) {
				$application->logger->debug('{method} - no databases, not loading configuration', $__);
			}
			return;
		}
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
		} catch (Throwable $e) {
			// Database is misconfigured/misnamed
			$exception = $e;
		}
		if ($exception) {
			$application->hooks->call('exception', $exception);
			$settings->setDatabaseDown($exception);
		}
	}

	/**
	 * @param Throwable|null $exception
	 * @return void
	 */
	private function setDatabaseDown(Throwable $exception = null): void {
		$this->db_down = $exception !== null;
		$this->db_down_why = $exception;
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
				'method' => __METHOD__, 'class' => __CLASS__, 'changes' => $this->changes, 'e' => $this->db_down_why,
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
	/**
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 * @throws InvalidArgumentException
	 * @throws Exception_Convert
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
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
						'class' => $settings::class, 'name' => $name,
					]);
				}
			} else {
				$settings->setMember('value', $value);
				$settings->store();
				if ($debug_save) {
					$settings->application->logger->debug('Saved {class} {name}={value}', [
						'class' => $settings::class, 'name' => $name, 'value' => $value,
					]);
				}
			}
		}
		$this->application->logger->debug('Deleted {class} cache', [
			'class' => __CLASS__,
		]);
		$this->application->cacheItemPool()->deleteItem(self::CACHE_ITEM_KEY);
		$this->changes = [];
	}

	/**
	 * Override get to retrieve from global state
	 *
	 * @param int|string $name Setting to retrieve
	 * @return mixed
	 */
	public function __get(int|string $name): mixed {
		return $this->application->configuration->getPath($name);
	}

	/**
	 * Same as __get with a default
	 *
	 * @see ORMBase::get()
	 * @param int|string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function get(int|string $name, mixed $default = null): mixed {
		return $this->application->configuration->getPath($name, $default);
	}

	/**
	 * @see Model::__isset()
	 * @param int|string $name
	 * @return bool
	 */
	public function __isset(int|string $name): bool {
		return $this->application->configuration->pathExists($name);
	}

	/**
	 * Global to save
	 *
	 * @see ORMBase::__set($member, $value)
	 */
	public function __set(int|string $name, mixed $value): void {
		$old_value = $this->application->configuration->getPath($name);
		if ($old_value === $value) {
			return;
		}

		$this->changes[$this->application->configuration->normalizeKey($name)] = $value;
		$this->application->configuration->setPath($name, $value);
	}

	/**
	 * Global to save
	 *
	 * @param int|string $name
	 * @param mixed|null $value
	 * @return self
	 * @see ORMBase::set($member, $value)
	 */
	public function set(int|string $name, mixed $value = null): self {
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
	public function meta(string $name): mixed {
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
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
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
	 * @throws Exception_Store
	 * @throws InvalidArgumentException
	 */
	public function setMeta(string $name, mixed $value): self {
		$this->__set($name, $value);
		$this->flush();
		return $this;
	}

	/**
	 *
	 * @param array|string $name
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
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
	 * @throws Exception_Store
	 * @throws InvalidArgumentException
	 * @see Interface_Data::deleteData()
	 */
	public function deleteData(array|string $name): self {
		foreach (toArray($name) as $item) {
			$this->__set($item, null);
		}
		$this->flush();
		return $this;
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
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @throws Database_Exception_NoResults
	 */
	public function prefixUpdated(string $old_prefix, string $new_prefix): int {
		$update = $this->application->ormRegistry(Settings::class)->queryUpdate();
		$old_prefix_quoted = $update->sql()->quoteText($old_prefix);
		$old_prefix_like_quoted = tr($old_prefix, [
			'\\' => '\\\\', '_' => '\\_',
		]);
		$rowCount = $update->value('*name', "REPLACE(name, $old_prefix_quoted, " . $update->database()->quoteText(strtolower($new_prefix)) . ')')->addWhere('name|LIKE', "$old_prefix_like_quoted%")->execute()->affectedRows();
		if ($rowCount > 0) {
			$this->application->logger->notice('Updated {rowCount} settings from {old_prefix} to use new prefix {new_prefix}', [
				'rowCount' => $rowCount, 'old_prefix' => $old_prefix, 'new_prefix' => $new_prefix,
			]);
		}
		return $rowCount;
	}
}
