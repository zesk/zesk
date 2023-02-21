<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;

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
	private string $path = '';

	/**
	 *
	 * @var CacheItem[]
	 */
	private array $deferred = [];

	/**
	 *
	 * @param string $path
	 * @throws DirectoryNotFound
	 */
	public function __construct(string $path) {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
		}
		$this->setPath($path);
	}

	/**
	 * Path setter/getter. Setting path will write deferred items to new path.
	 *
	 * @param string $path
	 * @return self
	 * @throws DirectoryNotFound
	 */
	public function setPath(string $path): self {
		if (!is_dir($path)) {
			throw new DirectoryNotFound($path);
		}
		$this->path = realpath($path);
		return $this;
	}

	/**
	 * Path getter
	 *
	 * @return string
	 */
	public function path(): string {
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
	 * @return CacheItemInterface
	 *   The corresponding Cache Item.
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function getItem(string $key): CacheItemInterface {
		$cache_file = $this->cacheFile($key);
		// Previously did "is_file", then "file_get_contents", but a race condition would create warnings in our logs when files were deleted
		// So, since file_get_contents is probably doing an is_file check internally anyway, just skip it since handling is identical
		$contents = is_readable($cache_file) ? file_get_contents($cache_file) : null;
		if (is_string($contents)) {
			try {
				$item = PHP::unserialize($contents);
				if ($item instanceof CacheItem && !$item->expired()) {
					return $item;
				}
			} catch (Throwable) {
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
	 * @return iterable
	 *   A traversable collection of Cache Items keyed by the cache keys of
	 *   each item. A Cache item will be returned for each key, even if that
	 *   key is not found. However, if no keys are specified then an empty
	 *   traversable MUST be returned instead.
	 * @throws InvalidArgumentException
	 *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function getItems(array $keys = []): iterable {
		$result = [];
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
	 * @return bool
	 *   True if item exists in the cache, false otherwise.
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function hasItem(string $key): bool {
		return is_file($this->cacheFile($key));
	}

	/**
	 * Deletes all items in the pool.
	 *
	 * @return bool
	 *   True if the pool was successfully cleared. False if there was an error.
	 */
	public function clear(): bool {
		try {
			Directory::deleteContents($this->path);
		} catch (FilePermission|DirectoryPermission|DirectoryNotFound) {
			return false;
		}
		return true;
	}

	/**
	 * Removes the item from the pool.
	 *
	 * @param string $key
	 *   The key to delete.
	 *
	 * 1     * @return bool
	 *   True if the item was successfully removed. False if there was an error.
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function deleteItem(string $key): bool {
		try {
			File::unlink($this->cacheFile($key));
			return true;
		} catch (FilePermission) {
			return false;
		}
	}

	/**
	 * Removes multiple items from the pool.
	 *
	 * @param string[] $keys
	 *   An array of keys that should be removed from the pool.
	 *
	 * @return bool
	 *   True if the items were successfully removed. False if there was an error.
	 * @throws InvalidArgumentException
	 *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function deleteItems(array $keys): bool {
		$success = true;
		foreach ($keys as $key) {
			if (!$this->deleteItem($key)) {
				$success = false;
			}
		}
		return $success;
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
	public function save(CacheItemInterface $item): bool {
		$key = $item->getKey();
		$file = $this->cacheFile($key);

		try {
			Directory::depend(dirname($file), 0o770);
			File::put($file, serialize($item));
			return true;
		} catch (DirectoryCreate|DirectoryPermission|FilePermission) {
			return false;
		}
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
	public function saveDeferred(CacheItemInterface $item): bool {
		$this->deferred[$this->cacheName($item->getKey())] = $item;
		return true;
	}

	/**
	 * Persists any deferred cache items.
	 *
	 * @return bool
	 *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
	 */
	public function commit(): bool {
		foreach ($this->deferred as $item) {
			$this->save($item);
		}
		$this->deferred = [];
		return true;
	}

	/**
	 * Returns filename of cache file
	 *
	 * @param string $key
	 * @return string
	 */
	private function cacheName(string $key): string {
		$clean = File::nameClean($key);
		$hash = md5($key);
		return substr($hash, 0, 1) . '/' . substr($hash, 1) . '^' . substr($clean, 0, 32);
	}

	/**
	 * Return full path to cache file
	 *
	 * @param string $key
	 * @return string
	 */
	private function cacheFile(string $key): string {
		return path($this->path, $this->cacheName($key));
	}
}
