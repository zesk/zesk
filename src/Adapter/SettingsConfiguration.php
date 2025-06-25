<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Adapter;

use zesk\Configuration;
use zesk\Exception\SemanticsException;
use zesk\Interface\SettingsInterface;

/**
 *
 */
class SettingsConfiguration implements SettingsInterface
{
    /**
     * @var Configuration
     */
    protected Configuration $configuration;

    /**
     *
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Is a value set in this object?
     *
     * @param int|string $key
     * @return boolean
     */
    public function __isset(int|string $key): bool
    {
        return $this->configuration->pathExists($key);
    }

    /**
     * Is a value set in this object?
     *
     * @param int|string $key
     * @return boolean
     */
    public function has(int|string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Retrieve a value from the settings
     *
     * @param mixed $key A string or key value (integer, float)
     * @return mixed The value of the session variable, or null if nothing set
     */
    public function __get(int|string $key): mixed
    {
        return $this->configuration->getPath($key);
    }

    /**
     * Retrieve a value from the settings, returning a default value if not set
     *
     * @param mixed $key A string or key value (integer, float)
     * @param mixed $default A value to return if the session value is null
     * @return mixed The value of the session variable, or $default if nothing set
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->configuration->getPath($key, $default);
    }

    /**
     * Store a value to a settings
     *
     * @param mixed $key A string or key value (integer, float)
     * @param mixed $value Value to save. As a general rule, best to use scalar types
     */
    public function __set(int|string $key, mixed $value): void
    {
        $this->configuration->setPath($key, $value);
    }

    /**
     * Store a value to a settings
     *
     * @param mixed $key A string or key value (integer, float)
     * @param mixed $value Value to save. As a general rule, best to use scalar types
     * @return self
     * @throws SemanticsException
     */
    public function set(int|string $key, mixed $value = null): self
    {
        $parent = $this->configuration->parentPath($key);
        if ($parent->isReadOnly()) {
            return $this;
        }
        $this->configuration->setPath($key, $value);
        return $this;
    }

    /**
     * Retrieve a list of all settings variables as an array
     *
     * @return iterable
     */
    public function variables(): iterable
    {
        return $this->configuration->toArray();
    }
}
