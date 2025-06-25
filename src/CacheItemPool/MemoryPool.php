<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\CacheItemPool;

use InvalidArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use zesk\CacheItem;

/**
 * The most basic of caching, storing everything in PHP memory as an array of keyed values.
 *
 * @author kent
 * @see    CacheItem
 */
class MemoryPool implements CacheItemPoolInterface
{
	/**
	 *
	 * @var array
	 */
	private array $items = [];

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
	 *   If the $key string is not a legal value, a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function getItem(string $key): CacheItemInterface
	{
		if (array_key_exists($key, $this->items)) {
			return $this->items[$key];
		}
		return new CacheItem($key, $this->items[$key] ?? null, $this->hasItem($key));
	}

	/**
	 * Returns a traversable set of cache items.
	 *
	 * @param string[] $keys
	 *   Keys of items to retrieve.
	 *
	 * @return iterable
	 *   A traversable collection of Cache Items keyed by the cache keys of
	 *   each item. A Cache item will be returned for each key, even if that
	 *   key is not found. However, if no keys are specified, then an empty
	 *   traversable MUST be returned instead.
	 * @throws InvalidArgumentException
	 *   If any of the keys in $keys are not a legal value, a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function getItems(array $keys = []): iterable
	{
		return array_map(fn ($key) => $this->getItem($key), $keys);
	}

	/**
	 * Confirms if the cache contains specified cache item.
	 *
	 * @param string $key
	 *   The key for which to check existence.
	 *
	 * @return bool
	 *   True if the item exists in the cache, false otherwise.
	 * @throws InvalidArgumentException
	 *   If the $key string is not a legal value, a \Psr\Cache\InvalidArgumentException
	 *   MUST be thrown.
	 *
	 */
	public function hasItem(string $key): bool
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Deletes all items in the pool.
	 *
	 * @return bool
	 *   True if the pool was successfully cleared. False if there was an error.
	 */
	public function clear(): bool
	{
		$this->items = [];
		return true;
	}

	/**
	 * Removes the item from the pool.
	 *
	 * @param string $key
	 *   The key to delete.
	 *
	 * @return bool
	 *   True if the item was successfully removed. False if there was an error.
	 */
	public function deleteItem(string $key): bool
	{
		unset($this->items[$key]);
		return true;
	}

	/**
	 * Removes multiple items from the pool.
	 *
	 * @param string[] $keys
	 *   An array of keys that should be removed from the pool.
	 *
	 * @return bool
	 *   True if the items were successfully removed. False if there was an error.
	 */
	public function deleteItems(array $keys): bool
	{
		$result = true;
		foreach ($keys as $key) {
			if (!$this->deleteItem($key)) {
				$result = false;
			}
		}
		return $result;
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
	public function save(CacheItemInterface $item): bool
	{
		$this->items[$item->getKey()] = $item;
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
	public function saveDeferred(CacheItemInterface $item): bool
	{
		$this->items[$item->getKey()] = $item;
		return false;
	}

	/**
	 * Persists any deferred cache items.
	 *
	 * @return bool
	 *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
	 */
	public function commit(): bool
	{
		return true;
	}
}
