<?php
/**
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 *
 * @author kent
 *
 */
class CacheItemPool_File implements CacheItemPoolInterface {
	/**
	 * 
	 * @var string
	 */
	private $path = null;
	
	/**
	 * 
	 * @var CacheItem[]
	 */
	private $deferred = array();
	
	/**
	 * 
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 */
	function __construct($path) {
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$this->path($path);
	}
	
	/**
	 * Path setter/getter. Setting path will write deferred items to new path.
	 * 
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 * @return \zesk\CacheItemPool_File|string
	 */
	function path($path = null) {
		if ($path !== null) {
			if (!is_dir($path)) {
				throw new Exception_Directory_NotFound($path);
			}
			$this->path = realpath($path);
			return $this;
		}
		return $this->path;
	}
	/**
	 * Returns a Cache Item representing the specified key.
	 *
	 * This method must always return a CacheItemInterface object, even in case of
	 * a cache miss. It MUST NOT return null.
	 *
	 * @param string $key
	 *   The key for which to return the corresponding Cache Item.
	 *
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 * @return CacheItemInterface
	 *   The corresponding Cache Item.
	 */
	public function getItem($key) {
		$cache_file = $this->cache_file($key);
		if (is_file($cache_file)) {
			try {
				$item = PHP::unserialize(file_get_contents($cache_file));
				if ($item instanceof CacheItem && !$item->expired()) {
					return $item;
				}
			} catch (\Exception $e) {
			}
		}
		return new CacheItem($key, null, false);
	}
	
	/**
	 * Returns a traversable set of cache items.
	 *
	 * @param string[] $keys
	 *   An indexed array of keys of items to retrieve.
	 *
	 * @throws InvalidArgumentException
	 *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 * @return array|\Traversable
	 *   A traversable collection of Cache Items keyed by the cache keys of
	 *   each item. A Cache item will be returned for each key, even if that
	 *   key is not found. However, if no keys are specified then an empty
	 *   traversable MUST be returned instead.
	 */
	public function getItems(array $keys = array()) {
		$result = array();
		foreach ($keys as $index => $key) {
			$result[$index] = $this->getItem($key);
		}
		return $result;
	}
	
	/**
	 * Confirms if the cache contains specified cache item.
	 *
	 * Note: This method MAY avoid retrieving the cached value for performance reasons.
	 * This could result in a race condition with CacheItemInterface::get(). To avoid
	 * such situation use CacheItemInterface::isHit() instead.
	 *
	 * @param string $key
	 *   The key for which to check existence.
	 *
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 * @return bool
	 *   True if item exists in the cache, false otherwise.
	 */
	public function hasItem($key) {
		return file_exists($this->cache_file($key));
	}
	
	/**
	 * Deletes all items in the pool.
	 *
	 * @return bool
	 *   True if the pool was successfully cleared. False if there was an error.
	 */
	public function clear() {
		Directory::empty($this->path);
		return true;
	}
	
	/**
	 * Removes the item from the pool.
	 *
	 * @param string $key
	 *   The key to delete.
	 *
	 1	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 * @return bool
	 *   True if the item was successfully removed. False if there was an error.
	 */
	public function deleteItem($key) {
		try {
			return File::unlink($this->cache_file($key));
		} catch (Exception_File_Permission $e) {
			return false;
		}
	}
	
	/**
	 * Removes multiple items from the pool.
	 *
	 * @param string[] $keys
	 *   An array of keys that should be removed from the pool.
	 
	 * @throws InvalidArgumentException
	 *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 * @return bool
	 *   True if the items were successfully removed. False if there was an error.
	 */
	public function deleteItems(array $keys) {
		foreach ($keys as $key) {
			$this->deleteItem($key);
		}
	}
	
	/**
	 * Persists a cache item immediately.
	 *
	 * @param CacheItemInterface $item
	 *   The cache item to save.
	 *
	 * @return bool
	 *   True if the item was successfully persisted. False if there was an error.
	 */
	public function save(CacheItemInterface $item) {
		$key = $item->getKey();
		File::put($this->cache_file($key), serialize($item));
		return false;
	}
	
	/**
	 * Sets a cache item to be persisted later.
	 *
	 * @param CacheItemInterface $item
	 *   The cache item to save.
	 *
	 * @return bool
	 *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
	 */
	public function saveDeferred(CacheItemInterface $item) {
		$this->deferred[$this->cache_name($item->getKey())] = $item;
		return true;
	}
	
	/**
	 * Persists any deferred cache items.
	 *
	 * @return bool
	 *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
	 */
	public function commit() {
		foreach ($this->deferred as $item) {
			$this->save($item);
		}
	}
	
	/**
	 * Returns filename of cache file
	 * 
	 * @param string $key
	 * @return string
	 */
	private function cache_name($key) {
		$clean = File::name_clean($key);
		return substr($key, 0, 32) . "^" . md5($key);
	}
	/**
	 * Return full path to cache file
	 * 
	 * @param string $key
	 * @return string
	 */
	private function cache_file($key) {
		return path($this->path, $this->cache_name($key));
	}
}