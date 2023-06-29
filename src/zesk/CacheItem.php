<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use DateInterval as NativeDateInterval;

/**
 * Generic cache item
 *
 * @author kent
 * @see CacheItemPool_Array
 */
class CacheItem implements CacheItemInterface
{
	/**
	 *
	 * @var string
	 */
	private string $key;

	/**
	 *
	 * @var mixed
	 */
	private mixed $value;

	/**
	 *
	 * @var mixed
	 */
	private bool $is_hit;

	/**
	 *
	 * @var ?Timestamp
	 */
	private ?Timestamp $expiration = null;

	/**
	 * CacheItem constructor.
	 * @param string $key
	 * @param mixed|null $value
	 * @param bool $isHit
	 */
	public function __construct(string $key, mixed $value = null, bool $isHit = true)
	{
		$this->key = $key;
		$this->value = $value;
		$this->is_hit = $isHit;
	}

	/**
	 * Returns the key for the current cache item.
	 *
	 * The key is loaded by the Implementing Library, but should be available to
	 * the higher level callers when needed.
	 *
	 * @return string
	 *   The key string for this cache item.
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Retrieves the value of the item from the cache associated with this object's key.
	 *
	 * The value returned must be identical to the value originally stored by set().
	 *
	 * If isHit() returns false, this method MUST return null. Note that null
	 * is a legitimate cached value, so the isHit() method SHOULD be used to
	 * differentiate between "null value was found" and "no value was found."
	 *
	 * @return mixed
	 *   The value corresponding to this cache item's key, or null if not found.
	 */
	public function get(): mixed
	{
		return $this->is_hit && !$this->expired() ? $this->value : null;
	}

	/**
	 * Confirms if the cache item lookup resulted in a cache hit.
	 *
	 * Note: This method MUST NOT have a race condition between calling isHit()
	 * and calling get().
	 *
	 * @return bool
	 *   True if the request resulted in a cache hit. False otherwise.
	 */
	public function isHit(): bool
	{
		return $this->is_hit && !$this->expired();
	}

	/**
	 * Sets the value represented by this cache item.
	 *
	 * The $value argument may be any item that can be serialized by PHP,
	 * although the method of serialization is left up to the Implementing
	 * Library.
	 *
	 * @param mixed $value
	 *   The serializable value to be stored.
	 *
	 * @return static
	 * @see CacheItemInterface::set()
	 */
	public function set(mixed $value): static
	{
		$this->is_hit = true;
		$this->value = $value;
		return $this;
	}

	/**
	 * Sets the expiration time for this cache item.
	 *
	 * @param DateTimeInterface|null $expiration
	 *   The point in time after which the item MUST be considered expired.
	 *   If null is passed explicitly, a default value MAY be used. If none is set,
	 *   the value should be stored permanently or for as long as the
	 *   implementation allows.
	 *
	 * @return static
	 *   The called object.
	 */
	public function expiresAt(DateTimeInterface|null $expiration): static
	{
		$this->expiration = $expiration ? Timestamp::factory($expiration) : null;
		return $this;
	}

	/**
	 * Sets the expiration time for this cache item.
	 *
	 * @param int|NativeDateInterval|null $time
	 *   The period of time from the present after which the item MUST be considered
	 *   expired. An integer parameter is understood to be the time in seconds until
	 *   expiration. If null is passed explicitly, a default value MAY be used.
	 *   If none is set, the value should be stored permanently or for as long as the
	 *   implementation allows.
	 *
	 * @return static
	 *   The called object.
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public function expiresAfter(int|NativeDateInterval|null $time): static
	{
		$this->expiration = $time ? Timestamp::now()->addUnit($time) : null;
		return $this;
	}

	/**
	 * @return Timestamp|null
	 */
	public function expiration(): ?Timestamp
	{
		return $this->expiration;
	}

	/**
	 * @return boolean
	 */
	public function expired(): bool
	{
		if (!$this->expiration instanceof Timestamp) {
			return false;
		}
		return $this->expiration->beforeNow();
	}
}
