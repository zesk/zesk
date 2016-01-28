<?php

interface Interface_Data {
	/**
	 * Getter/setter for data.
	 *
	 * When value === null, gets data, otherwise sets it
	 *
	 * This method should have immediate effect in the database
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return mixed|Interface_Data
	 */
	function data($name, $value = null);

	/**
	 * Delete data
	 *
	 * This method should have immediate effect in the database
	 *
	 * @param string $name
	 * @return boolean
	 */
	function delete_data($name);
}