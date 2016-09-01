<?php

namespace zesk;

interface Interface_Data {
	/**
	 * Getter/setter for data.
	 *
	 * When value === null, gets data, otherwise sets it
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return mixed|Interface_Data
	 */
	function data($name, $value = null);
	
	/**
	 * Delete data
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param string|list $name
	 * @return boolean
	 */
	function delete_data($name);
}