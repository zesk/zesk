<?php declare(strict_types=1);
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
	public function data($name, $value = null);

	/**
	 * Delete data
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param string|list $name
	 * @return boolean
	 */
	public function delete_data($name);
}
