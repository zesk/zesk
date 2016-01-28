<?php

interface Interface_Settings {
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
	 * Retrieve a value from the settings, returning a default value if empty or not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function eget($name, $default = null);

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
	 * @return Interface_Settings
	 */
	public function set($name, $value = null);

	/**
	 * Retrieve a list of settings variables as an array (similar to arr::filter)
	 *
	 * @param mixed $list A ;-separated list, or an array of keys, or an array of keys mapped to destination keys
	 * @return Iterator
	 */
	public function filter($mixed = null);

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return Iterator
	*/
	public function variables();
}