<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

use ArrayAccess;
use Countable;
use Iterator;
use zesk\Exception\Deprecated;
use zesk\Exception\SemanticsException;

/**
 *
 * @author kent
 *
 */
class Configuration implements Iterator, Countable, ArrayAccess
{
    use GetTyped;

    /**
     * When we pass strings into methods as paths, this sequence of characters is equivalent to a
     * traversal from parent Configuration to a child Configuration object.
     *
     * @var string
     */
    public const key_separator = '::';

    public function __serialize(): array
    {
        return [
            'path' => $this->path,
            'keys' => $this->keys,
            'readOnly' => $this->readOnly,
        ];
        // return array_merge(..., parent::__serialize());
    }

    public function __unserialize(array $data): void
    {
        $this->path = $data['path'];
        $this->keys = $data['keys'];
        $this->readOnly = $data['readOnly'];
        $this->index = 0;
        $this->count = count($this->keys);
        $this->skipNext = false;
        // parent::__unserialize($data);
    }

    /**
     * Path to get to this configuration location
     */
    protected array $path = [];

    /**
     * Our datum, datum, datum, datum, datum, datum, datum, dat-u-u-u-um.
     *
     * Key value pairs. Lots of Configuration objects, or non-Configuration values
     *
     * @var Configuration[]
     */
    protected array $keys;

    /**
     * Current iterator index. For iterating, silly.
     */
    protected int $index;

    /**
     * Number of configuration items
     */
    protected int $count;

    /**
     * Skip the next iteration
     */
    protected bool $skipNext = false;

    /**
     * @var bool
     */
    private bool $readOnly = false;

    public function setReadOnly(bool $readOnly): self
    {
        $this->readOnly = $readOnly;
        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Configuration constructor.
     *
     * @param array $array
     * @param array $path
     */
    public function __construct(array $array = [], array $path = [])
    {
        $this->path = $path;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = new self($value, $this->_addPath($key));
            }
        }
        $this->keys = $array;
        $this->index = 0;
        $this->count = count($this->keys);
    }

    public static function normalizeKey(string $key): string
    {
        return Types::keyToString(Types::configurationKey($key));
    }

    /**
     *
     * @param array $array
     * @param array $path
     * @return self
     */
    public static function factory(array $array = [], array $path = []): self
    {
        return new self($array, $path);
    }

    /**
     * Copy a configuration
     */
    public function __clone()
    {
        $array = [];
        foreach ($this->keys as $key => $value) {
            if ($value instanceof self) {
                $array[$key] = clone $value;
            } else {
                $array[$key] = $value;
            }
        }
        $this->keys = $array;
    }

    /**
     * @param string $context
     * @return void
     * @throws SemanticsException
     */
    protected function writeAttempt(string $context = ''): void
    {
        if ($this->readOnly) {
            $path = implode(' -> ', $this->path);

            throw new SemanticsException("Write attempted to read only Configuration ($path ->: $context) " . spl_object_hash($this));
        }
    }

    /**
     * Merge two configurations, skipping any read-only Configurations in the current object.
     *
     * Passed in configuration will, by default, override similar keys and paths in
     * the current object.
     *
     * @param Configuration $config
     * @param boolean $overwrite
     * @return self
     * @throws SemanticsException
     */
    public function merge(Configuration $config, bool $overwrite = true): self
    {
        $this->writeAttempt();
        foreach ($config as $key => $value) {
            if (isset($this->keys[$key])) {
                $this_value = $this->keys[$key];
                if ($value instanceof self && $this_value instanceof self) {
                    if (!$this_value->isReadOnly()) {
                        $this_value->merge($value, $overwrite);
                    }
                } elseif ($overwrite) {
                    $this->keys[$key] = $value instanceof self ? clone $value : $value;
                }
            } else {
                $this->keys[$key] = $value instanceof self ? clone $value : $value;
            }
        }
        $this->count = count($this->keys);
        return $this;
    }

    /**
     *
     * @param string|int $key
     * @return boolean
     */
    public function __isset(string|int $key): bool
    {
        return isset($this->keys[$key]);
    }

    /**
     * @param string|int $key
     * @return void
     */
    public function __unset(string|int $key): void
    {
        $this->writeAttempt();
        if (isset($this->keys[$key])) {
            unset($this->keys[$key]);
            $this->count = count($this->keys);
        }
    }

    /**
     * @param string|array $key
     * @param mixed|null $value
     * @return $this
     */
    public function set(string|array $key, mixed $value = null): self
    {
        $this->writeAttempt();
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->__set($k, $v);
            }
            return $this;
        }
        $this->__set($key, $value);
        return $this;
    }

    /**
     * Set a value in a configuration object. Setting a value to null is the same as deleting it.
     *
     * @param string|int $key
     * @param mixed $value
     * @return void
     */
    public function __set(string|int $key, mixed $value): void
    {
        $this->writeAttempt();
        if (is_array($value) && ArrayTools::isAssoc($value)) {
            $value = new self($value, $this->_addPath($key));
        }
        if ($value === null) {
            unset($this->keys[$key]);
        } else {
            $this->keys[$key] = $value;
        }
        $this->count = count($this->keys);
    }

    /**
     * Given a list of paths into the configuration tree, return the first path which has a value.
     *
     * @param array $paths
     * @param mixed $default
     * @return mixed
     */
    public function getFirstPath(array $paths, mixed $default = null): mixed
    {
        foreach ($paths as $path) {
            if (($result = $this->getPath($path)) !== null) {
                return $result;
            }
        }
        return $default;
    }

    /**
     * Set multiple paths to multiple values
     *
     * @param array $paths
     * @return Configuration[]
     */
    public function setPaths(array $paths): array
    {
        $this->writeAttempt();
        $result = [];
        foreach ($paths as $path => $value) {
            $result[$path] = $this->setPath(Types::toList($path, [], self::key_separator), $value);
        }
        return $result;
    }

    /**
     * Retrieve a path using self::key_separator
     *
     * @param string|array $path
     * @param mixed $default
     * @return mixed
     */
    public function getPath(string|array $path, mixed $default = null): mixed
    {
        $path = is_array($path) ? $path : explode(self::key_separator, $path);
        $current = $this;
        $key = array_pop($path);
        foreach ($path as $section) {
            $current = $current->$section;
            if (!$current instanceof self) {
                return $default;
            }
        }
        $result = $current->get($key, $default);
        return $result instanceof self ? $result->toArray() : $result;
    }

    /**
     * Retrieve a path and fetch a string value
     *
     * @param string|array $path
     * @param string $default
     * @return string
     */
    public function getPathString(string|array $path, string $default = ''): string
    {
        $path = $this->getPath($path);
        return is_string($path) ? $path : $default;
    }

    /**
     * Retrieve a path and fetch a string value
     *
     * @param string|array $path
     * @param int $default
     * @return int
     */
    public function getPathInt(string|array $path, int $default = 0): int
    {
        $path = $this->getPath($path);
        return is_int($path) ? $path : $default;
    }

    /**
     * Retrieve a path and fetch a float value
     *
     * @param string|array $path
     * @param float $default
     * @return float
     */
    public function getPathFloat(string|array $path, float $default = 0.0): float
    {
        $path = $this->getPath($path);
        return is_numeric($path) ? $path : $default;
    }

    /**
     * Does a path exist?
     *
     * @param string|array $path
     * @return bool
     */
    public function pathExists(string|array $path): bool
    {
        $path = is_array($path) ? $path : explode(self::key_separator, $path);
        $current = $this;
        $key = array_pop($path);
        foreach ($path as $section) {
            $current = $current->$section;
            if (!$current instanceof self) {
                return false;
            }
        }
        return $current->has($key);
    }

    /**
     *
     * @param string|int $key
     * @return self
     */
    public function __get(string|int $key)
    {
        return $this->keys[$key] ?? null;
    }

    /**
     * Given a path into the configuration tree, set a value
     *
     * @param string|array $path
     * @param mixed $value
     * @return self returns self always
     * @throws SemanticsException
     */
    public function setPath(string|array $path, mixed $value = null): self
    {
        $path = is_array($path) ? $path : explode(self::key_separator, $path);
        $this->writeAttempt(implode(' -> ', $path));
        $key = array_pop($path);
        if (count($path) > 0) {
            $this->path($path)->setPath($key, $value);
            return $this;
        }
        $this->set($key, $value);
        return $this;
    }

    /**
     * Ensure configuration path is available
     *
     * @param array|string $keys
     * @return self
     * @throws SemanticsException
     */
    public function path(string|array $keys): self
    {
        $this->writeAttempt();
        $keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
        $current = $this;
        while (count($keys) > 0) {
            $next = array_shift($keys);
            if (!$current->$next instanceof self) {
                $current->set($next, new Configuration());
            }
            $current = $current->$next;
        }
        return is_array($current) ? new Configuration($current) : $current;
    }

    /**
     * Walk configuration and return found value, or default if not found
     *
     * @param string|array $keys
     * @param mixed $default
     * @return mixed
     */
    public function walk(string|array $keys, mixed $default = null): mixed
    {
        $keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
        $current = $this;
        while (count($keys) > 0) {
            if (!$current instanceof self) {
                return $default;
            }
            $next = array_shift($keys);
            $current = $current->__get($next);
        }
        return $current;
    }

    /**
     * Retrieve value
     */
    public function value(): array
    {
        return $this->keys;
    }

    /**
     * Convert an entire structure to an array, recursively
     *
     * @param int|null $depth How deep to traverse (null for infinite)
     * @return array
     */
    public function toArray(?int $depth = null): array
    {
        $result = [];
        foreach ($this->keys as $key => $value) {
            if ($value instanceof self) {
                if ($depth === null || $depth > 0) {
                    $result[$key] = $value->toArray($depth > 0 ? $depth - 1 : null);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Convert the entire structure to a list
     *
     * @return array
     */
    public function toList(): array
    {
        $result = [];
        foreach ($this->keys as $value) {
            if ($value instanceof self) {
                continue;
            }
            $result[] = $value;
        }
        return $result;
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     *
     */
    public function current(): mixed
    {
        $this->skipNext = false;
        return current($this->keys);
    }

    /**
     *
     * @return string|int|null
     * @see Iterator
     *
     */
    public function key(): string|int|null
    {
        return key($this->keys);
    }

    /**
     *
     * @see Iterator
     */
    public function next(): void
    {
        if ($this->skipNext) {
            $this->skipNext = false;
            return;
        }
        next($this->keys);
        $this->index++;
    }

    /**
     *
     * @see Iterator
     */
    public function rewind(): void
    {
        $this->skipNext = false;
        reset($this->keys);
        $this->index = 0;
    }

    /**
     *
     * @return boolean
     * @see Iterator
     *
     */
    public function valid(): bool
    {
        return $this->index < $this->count;
    }

    /**
     *
     * @return integer
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     *
     * @param mixed $offset
     * @return boolean
     * @see ArrayAccess
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     *
     * @param mixed $offset
     * @return mixed
     * @see ArrayAccess
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     *
     * @param mixed $offset
     * @param mixed $value
     * @see ArrayAccess
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     *
     * @param mixed $offset
     * @see ArrayAccess
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->__unset($offset);
    }

    /**
     * Add key to an existing path and return a new path
     *
     * @param string $key
     * @return array
     */
    private function _addPath(string $key): array
    {
        return array_merge($this->path, [
            $key,
        ]);
    }

    /**
     * Returns true if old configuration option is still being used
     *
     * @param array|string $old_path
     * @param string|array|null $new_path
     * @return boolean Returns true if OLD value still found and (optionally) mapped to new
     * @throws Deprecated
     */
    final public function deprecated(string|array $old_path, string|array|null $new_path = null): bool
    {
        $old_value = $this->walk($old_path);
        if ($old_value === null) {
            return false;
        }
        $app = Kernel::singleton()->application();
        $logger = $app->logger();
        if ($new_path == null) {
            $logger->warning('Global configuration option {old_path} is deprecated, remove it', compact('old_path'));
            return true;
        }
        $message_args = [];
        if (!$this->pathExists($new_path)) {
            $this->setPath($new_path, $old_value);
            $message = 'Global configuration option "{old_path}" is deprecated ({old_value}), use existing "{new_path}"';
            $message_args['old_value'] = Types::toArray($old_value);
        } else {
            $new_value = $this->walk($new_path);
            if ($new_value instanceof self && $old_value instanceof self) {
                $message = 'Global configuration option {old_path} is deprecated ({old_value}), use existing "{new_path}" (merged)';
                $new_value->merge($old_value);
                $this->setPath($old_path);
                $message_args['old_value'] = Types::toArray($old_value);
            } else {
                $message = 'Global configuration option {old_path} ({old_type}) is deprecated, use existing {new_path} (NOT merged)';
            }
        }
        if (is_array($old_path)) {
            $old_path = implode(self::key_separator, $old_path);
        }
        if (is_array($new_path)) {
            $new_path = implode(self::key_separator, $new_path);
        }
        $message_args += compact('old_path', 'new_path') + [
                'old_type' => Types::type($old_value),
            ];
        $logger->warning($message, $message_args);
        $app->deprecated($message, $message_args);

        return true;
    }

    /**
     * Fetch the keys for the values in the configuration
     *
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys($this->keys);
    }

    /**
     * Return the parent Configuration for a value
     *
     * @param string|array $key
     * @return $this|self
     * @throws SemanticsException
     */
    public function parentPath(string|array $key): self
    {
        $key = is_array($key) ? $key : explode(self::key_separator, $key);
        array_pop($key);
        if (count($key) === 0) {
            return $this;
        }
        return $this->path($key);
    }
}
