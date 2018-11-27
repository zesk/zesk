<?php
/**
 * Define an interface to name/value pairs
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Settings {
    /**
     * Is a value set in this object?
     * @return boolean
     */
    public function __isset($name);

    /**
     * Is a value set in this object?
     * @return boolean
     */
    public function has($name);

    /**
     * Retrieve a value from the settings
     * @param mixed $name A string or key value (integer, float)
     * @return mixed The value of the session variable, or null if nothing set
     */
    public function __get($name);

    /**
     * Retrieve a value from the settings, returning a default value if not set
     * @param mixed $name A string or key value (integer, float)
     * @param mixed $default A value to return if the session value is null
     * @return mixed The value of the session variable, or $default if nothing set
     */
    public function get($name = null, $default = null);

    /**
     * Store a value to a settings
     *
     * @param mixed $name A string or key value (integer, float)
     * @param mixed $value Value to save. As a general rule, best to use scalar types
     */
    public function __set($name, $value);

    /**
     * Store a value to a settings
     *
     * @param mixed $name A string or key value (integer, float)
     * @param mixed $value Value to save. As a general rule, best to use scalar types
     * @return self
     */
    public function set($name, $value = null);

    /**
     * Retrieve a list of all settings variables as an array
     *
     * @return Iterator
     */
    public function variables();
}
