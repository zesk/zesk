<?php
declare(strict_types=1);

namespace zesk;

interface Interface_Data {
	/**
	 * Getter for data.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function data(string $name): mixed;

	/**
	 * Setter for data.
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setData(string $name, mixed $value): self;

	/**
	 * Delete data
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param array|string $name of datum to delete
	 * @return $this
	 */
	public function deleteData(array|string $name): self;
}
